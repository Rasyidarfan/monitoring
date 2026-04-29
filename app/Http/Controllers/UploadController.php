<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Anomaly;
use App\Models\AnomalyData;
use App\Models\MonitoringData;
use App\Models\PjMapping;
use App\Services\CsvMappingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use ZipArchive;

class UploadController extends Controller
{
    /**
     * Show upload form for specific activity
     */
    public function show(Activity $activity): View
    {
        // Get upload history
        $uploadHistory = $activity->uploadHistories()
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.upload.index', compact('activity', 'uploadHistory'));
    }

    /**
     * Get old target values for reconciliation
     */
    private function getOldTargets(Activity $activity): array
    {
        return $activity->pjMappings()
            ->pluck('target', 'village_code')
            ->toArray();
    }

    /**
     * Upload JSON file (PJ Mapping) - SYNC Mode
     */
    public function uploadJson(Request $request, Activity $activity): RedirectResponse
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json,txt|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('json_file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
            }

            // Expected format: [{"Id": "9702010001", "PJ": "Ibu Farah"}, ...]
            if (!is_array($data)) {
                throw new \Exception('JSON must be an array of objects');
            }

            // Get village names from monitoring_data for desa_nama
            $desaNames = MonitoringData::where('activity_id', $activity->id)
                ->pluck('village_name', 'village_code')
                ->toArray();

            $created = 0;
            $updated = 0;
            $deleted = 0;
            $skipped = 0;
            $processedCodes = [];
            $jsonVillageCodes = [];

            // SYNC Mode: Process JSON entries (INSERT or UPDATE)
            foreach ($data as $item) {
                if (!isset($item['Id']) || !isset($item['PJ'])) {
                    $skipped++;
                    continue;
                }

                $villageCode = $item['Id'];
                $pjName = $item['PJ'];

                // Skip if duplicate village_code in same file
                if (in_array($villageCode, $processedCodes)) {
                    $skipped++;
                    continue;
                }

                $processedCodes[] = $villageCode;
                $jsonVillageCodes[] = $villageCode;

                $desaNama = $desaNames[$villageCode] ?? null;

                // Check if already exists
                $existing = $activity->pjMappings()
                    ->where('village_code', $villageCode)
                    ->first();

                if ($existing) {
                    // UPDATE: only update pj_name and desa_nama, preserve target
                    $existing->update([
                        'pj_name' => $pjName,
                        'desa_nama' => $desaNama,
                    ]);
                    $updated++;
                } else {
                    // INSERT: new entry with target=0
                    $activity->pjMappings()->create([
                        'village_code' => $villageCode,
                        'desa_nama' => $desaNama,
                        'pj_code' => $villageCode,
                        'pj_name' => $pjName,
                        'target' => 0,
                    ]);
                    $created++;
                }
            }

            // DELETE: entries in DB but not in JSON file
            $toDelete = $activity->pjMappings()
                ->whereNotIn('village_code', $jsonVillageCodes)
                ->get();

            foreach ($toDelete as $pj) {
                $pj->delete();
                $deleted++;
            }

            // Save filename
            $filename = $file->getClientOriginalName();
            $activity->update([
                'json_filename' => $filename,
                'last_data_upload_at' => now(),
            ]);

            // Log upload history
            $activity->uploadHistories()->create([
                'uploaded_by' => auth()->id(),
                'file_type' => 'json',
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'file_size' => $file->getSize(),
                'records_imported' => $created + $updated,
                'status' => 'completed',
            ]);

            $message = "JSON uploaded successfully!";
            if ($created > 0) $message .= " Created {$created}.";
            if ($updated > 0) $message .= " Updated {$updated}.";
            if ($deleted > 0) $message .= " Deleted {$deleted}.";
            if ($skipped > 0) $message .= " Skipped {$skipped}.";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading JSON: ' . $e->getMessage());
        }
    }

    /**
     * Upload CSV file (Monitoring Data) - SYNC Mode
     * Keep existing pj_mappings, update desa_nama and target (using MAX logic)
     * Replace monitoring_data metrics with CSV values
     */
    public function uploadCsv(Request $request, Activity $activity): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('csv_file');
            $csvMapper = new CsvMappingService();

            $handle = fopen($file->getRealPath(), 'r');

            // Read header
            $header = fgetcsv($handle);
            if (!$header) {
                throw new \Exception('CSV file is empty');
            }

            // SYNC Mode: Clear monitoring data, keep pj_mappings
            MonitoringData::where('activity_id', $activity->id)->delete();

            // Create flexible header mapping
            $headerMap = $csvMapper->mapHeaders($header);

            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            while (($row = fgetcsv($handle)) !== false) {
                // Find Desa column (first column should be Desa)
                $desaField = $row[0] ?? null;
                if (!$desaField) {
                    $skipped++;
                    continue;
                }

                // Parse village code and name
                $villageInfo = $csvMapper->parseVillageField($desaField);
                if (!$villageInfo) {
                    $skipped++;
                    continue;
                }

                $villageCode = $villageInfo['code'];
                $villageName = $villageInfo['name'];
                $regencyCode = substr($villageCode, 0, 4);

                // Extract values using flexible mapping
                $values = $csvMapper->extractValues($row, $headerMap);

                // Skip if target = 0 (assignment not assigned)
                if ($values['target'] == 0) {
                    $skipped++;
                    continue;
                }

                // Check if pj_mapping exists
                $pjMapping = $activity->pjMappings()
                    ->where('village_code', $villageCode)
                    ->first();

                if ($pjMapping) {
                    // UPDATE: Apply MAX logic - use largest target value
                    $newTarget = max($pjMapping->target, $values['target']);

                    $pjMapping->update([
                        'desa_nama' => $villageName,
                        'target' => $newTarget,
                    ]);
                    $updated++;
                } else {
                    // INSERT: Create new pj_mapping with target from CSV
                    $activity->pjMappings()->create([
                        'village_code' => $villageCode,
                        'desa_nama' => $villageName,
                        'pj_code' => null,
                        'pj_name' => null,
                        'target' => $values['target'],
                    ]);
                    $inserted++;
                }

                // Insert monitoring data with CSV values (replace, don't use MAX for metrics)
                MonitoringData::create([
                    'activity_id' => $activity->id,
                    'village_code' => $villageCode,
                    'village_name' => $villageName,
                    'regency_code' => $regencyCode,
                    'open' => $values['open'],
                    'submitted' => $values['submitted'],
                    'approved' => $values['approved'],
                    'rejected' => $values['rejected'],
                ]);
            }

            fclose($handle);

            // Save filename
            $filename = $file->getClientOriginalName();
            $activity->update(['last_data_upload_at' => now()]);

            // Log upload history
            $activity->uploadHistories()->create([
                'uploaded_by' => auth()->id(),
                'file_type' => 'csv',
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'file_size' => $file->getSize(),
                'records_imported' => $inserted + $updated,
                'status' => 'completed',
            ]);

            $message = "CSV uploaded successfully!";
            if ($inserted > 0) $message .= " Imported: {$inserted}.";
            if ($updated > 0) $message .= " Updated: {$updated}.";
            if ($skipped > 0) $message .= " Skipped: {$skipped} (target=0 or invalid).";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading CSV: ' . $e->getMessage());
        }
    }

    /**
     * Upload ZIP file - SYNC Mode
     * Keep existing pj_mappings, update desa_nama and target (using MAX logic)
     * Replace monitoring_data metrics with ZIP values
     */
    public function uploadZip(Request $request, Activity $activity): RedirectResponse
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:51200', // 50MB
        ]);

        try {
            $file = $request->file('zip_file');
            $zip = new ZipArchive();

            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Failed to open ZIP file');
            }

            // Create temp directory if not exists
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Extract to temp directory
            $extractPath = $tempDir . '/' . uniqid();
            if (!$zip->extractTo($extractPath)) {
                $zip->close();
                throw new \Exception('Failed to extract ZIP file');
            }
            $zip->close();

            // Look for both query_1.csv and query_2.csv (check both root and subdirectory)
            $csvFile1 = null;
            $csvFile2 = null;

            if (file_exists($extractPath . '/query_1.csv')) {
                $csvFile1 = $extractPath . '/query_1.csv';
            } else {
                // Check subdirectories
                $files = glob($extractPath . '/*/query_1.csv');
                if (!empty($files)) {
                    $csvFile1 = $files[0];
                }
            }

            if (file_exists($extractPath . '/query_2.csv')) {
                $csvFile2 = $extractPath . '/query_2.csv';
            } else {
                // Check subdirectories
                $files = glob($extractPath . '/*/query_2.csv');
                if (!empty($files)) {
                    $csvFile2 = $files[0];
                }
            }

            if (!$csvFile1 || !file_exists($csvFile1)) {
                // Clean up
                $this->deleteDirectory($extractPath);
                throw new \Exception('query_1.csv not found in ZIP file');
            }

            // SYNC Mode: Clear monitoring data, keep pj_mappings
            MonitoringData::where('activity_id', $activity->id)->delete();

            // Process CSV with flexible mapping
            $csvMapper = new CsvMappingService();

            // ==================== PROCESS query_1.csv (Village-level details) ====================
            $handle = fopen($csvFile1, 'r');
            $header = fgetcsv($handle);

            // Create flexible header mapping
            $headerMap = $csvMapper->mapHeaders($header);

            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            while (($row = fgetcsv($handle)) !== false) {
                // Find Desa column (first column should be Desa)
                $desaField = $row[0] ?? null;
                if (!$desaField) {
                    $skipped++;
                    continue;
                }

                // Parse village code and name
                $villageInfo = $csvMapper->parseVillageField($desaField);
                if (!$villageInfo) {
                    $skipped++;
                    continue;
                }

                $villageCode = $villageInfo['code'];
                $villageName = $villageInfo['name'];
                $regencyCode = substr($villageCode, 0, 4);

                // Extract values using flexible mapping
                $values = $csvMapper->extractValues($row, $headerMap);

                // Skip if target = 0 (assignment not assigned)
                if ($values['target'] == 0) {
                    $skipped++;
                    continue;
                }

                // Check if pj_mapping exists
                $pjMapping = $activity->pjMappings()
                    ->where('village_code', $villageCode)
                    ->first();

                if ($pjMapping) {
                    // UPDATE: Apply MAX logic - use largest target value
                    $newTarget = max($pjMapping->target, $values['target']);

                    $pjMapping->update([
                        'desa_nama' => $villageName,
                        'target' => $newTarget,
                    ]);
                    $updated++;
                } else {
                    // INSERT: Create new pj_mapping with target from ZIP
                    $activity->pjMappings()->create([
                        'village_code' => $villageCode,
                        'desa_nama' => $villageName,
                        'pj_code' => null,
                        'pj_name' => null,
                        'target' => $values['target'],
                    ]);
                    $inserted++;
                }

                // Insert monitoring data with ZIP values (replace, don't use MAX for metrics)
                MonitoringData::create([
                    'activity_id' => $activity->id,
                    'village_code' => $villageCode,
                    'village_name' => $villageName,
                    'regency_code' => $regencyCode,
                    'open' => $values['open'],
                    'submitted' => $values['submitted'],
                    'approved' => $values['approved'],
                    'rejected' => $values['rejected'],
                ]);
            }

            fclose($handle);

            // ==================== PROCESS query_2.csv (Summary/Total) if exists ====================
            $query2Stats = [];
            if ($csvFile2 && file_exists($csvFile2)) {
                $handle = fopen($csvFile2, 'r');
                $header = fgetcsv($handle);
                $headerMap = $csvMapper->mapHeaders($header);

                // query_2.csv usually has only 1 row with totals
                if (($row = fgetcsv($handle)) !== false) {
                    $query2Stats = $csvMapper->extractValues($row, $headerMap);
                }
                fclose($handle);
            }

            // Clean up
            $this->deleteDirectory($extractPath);

            // Save filename
            $filename = $file->getClientOriginalName();
            $activity->update([
                'zip_filename' => $filename,
                'last_data_upload_at' => now(),
            ]);

            // Log upload history
            $activity->uploadHistories()->create([
                'uploaded_by' => auth()->id(),
                'file_type' => 'zip',
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'file_size' => $file->getSize(),
                'records_imported' => $inserted + $updated,
                'status' => 'completed',
            ]);

            $message = "ZIP uploaded successfully!";
            $message .= " query_1.csv: {$inserted} imported, {$updated} updated.";
            if ($skipped > 0) $message .= " Skipped: {$skipped} (target=0 or invalid).";
            if (!empty($query2Stats)) {
                $message .= " query_2.csv summary: T={$query2Stats['target']}, O={$query2Stats['open']}, S={$query2Stats['submitted']}, A={$query2Stats['approved']}, R={$query2Stats['rejected']}.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading ZIP: ' . $e->getMessage());
        }
    }

    /**
     * Upload JSON file (Anomaly Master Data) - SYNC Mode
     * Replace anomalies master data with new JSON data
     * Format: [{"No. Anomali": "A01", "Rule dg Nama Variable di FASIH": "...", "Keterangan ...": "..."}, ...]
     */
    public function uploadAnomalyJson(Request $request, Activity $activity): RedirectResponse
    {
        $request->validate([
            'anomaly_json_file' => 'required|file|mimes:json,txt|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('anomaly_json_file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
            }

            if (!is_array($data)) {
                throw new \Exception('JSON must be an array of anomaly objects');
            }

            // SYNC Mode: Clear and reload anomalies master data
            Anomaly::truncate();

            $inserted = 0;
            $skipped = 0;

            foreach ($data as $item) {
                $code = $item['No. Anomali'] ?? null;
                $rule = $item['Rule dg Nama Variable di FASIH'] ?? null;
                $description = $item['Keterangan (disesuaikan dg variabel FASIH)'] ?? null;

                // Handle alternative description field name
                if (!$description) {
                    $description = $item['Keterangan'] ?? null;
                }

                if (!$code || !$description) {
                    $skipped++;
                    continue;
                }

                Anomaly::create([
                    'code' => $code,
                    'rule' => $rule,
                    'description' => $description,
                ]);

                $inserted++;
            }

            // Update activity's last_data_upload_at
            $filename = $file->getClientOriginalName();
            $activity->update(['last_data_upload_at' => now()]);

            // Log upload history
            $activity->uploadHistories()->create([
                'uploaded_by' => auth()->id(),
                'file_type' => 'anomaly_json',
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'file_size' => $file->getSize(),
                'records_imported' => $inserted,
                'status' => 'completed',
            ]);

            $message = "Anomaly JSON uploaded successfully! Imported: {$inserted}.";
            if ($skipped > 0) $message .= " Skipped: {$skipped} (invalid format).";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading Anomaly JSON: ' . $e->getMessage());
        }
    }

    /**
     * Upload CSV file (Anomaly Data) - SYNC Mode
     * Replace all anomaly_data for this activity with new CSV data
     */
    public function uploadAnomalyCSV(Request $request, Activity $activity): RedirectResponse
    {
        $request->validate([
            'anomaly_csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('anomaly_csv_file');
            $handle = fopen($file->getRealPath(), 'r');

            // Read header
            $header = fgetcsv($handle);
            if (!$header) {
                throw new \Exception('CSV file is empty');
            }

            $headerMap = array_flip(array_map('strtoupper', $header));

            // Flexible header mapping - support both PODES and ART-level formats
            $kodeIndex = $headerMap['KODE_DAERAH'] ?? $headerMap['KODE_DESA'] ?? null;
            $kecIndex = $headerMap['KEC'] ?? $headerMap['KECAMATAN'] ?? null;
            $desaIndex = $headerMap['DESA'] ?? null;
            $linkIndex = $headerMap['LINK'] ?? $headerMap['ASSIGNMENT_ID'] ?? null;
            $anomaliIndex = $headerMap['ANOMALI'] ?? $headerMap['KODE_ANOMALI'] ?? null;

            if ($kodeIndex === null || $anomaliIndex === null) {
                throw new \Exception('Missing required columns. Need at least: KODE_DAERAH/Kode_Desa and ANOMALI/Kode_Anomali');
            }

            // Detect format: PODES (no DSRT) or ART-level (has DSRT)
            $isPODES = !isset($headerMap['DSRT']) && isset($headerMap['KODE_DESA']);

            // MERGE Mode: Do NOT delete existing data, upsert per anomali
            $inserted = 0;
            $skipped = 0;

            while (($row = fgetcsv($handle)) !== false) {
                // Extract basic fields
                $kodeDaerah = trim($row[$kodeIndex] ?? '');
                $kecamatan = trim($row[$kecIndex] ?? '');
                $desa = trim($row[$desaIndex] ?? '');
                $link = trim($row[$linkIndex] ?? '');

                // Handle PODES vs ART-level data
                if ($isPODES) {
                    // PODES format: use dummy values for missing columns
                    $dsrt = 'PODES';
                    $noArt = 0;
                    $namaKrt = !empty($desa) ? $desa : 'ANOMALI DESA';
                    $namaArt = 'Anomali Desa-Level';
                } else {
                    // ART-level format: extract from CSV headers
                    $dsrtIndex = $headerMap['DSRT'] ?? null;
                    $noArtIndex = $headerMap['NO_ART'] ?? null;
                    $namaKrtIndex = $headerMap['NAMA_KRT'] ?? null;
                    $namaArtIndex = $headerMap['NAMA_ART'] ?? null;

                    $dsrt = trim($row[$dsrtIndex] ?? '');
                    $noArt = intval($row[$noArtIndex] ?? 0);
                    $namaKrt = trim($row[$namaKrtIndex] ?? '');
                    $namaArt = trim($row[$namaArtIndex] ?? '');

                    if (!$dsrt || !$namaKrt || !$namaArt) {
                        $skipped++;
                        continue;
                    }
                }

                // Validate minimum required fields
                if (!$kodeDaerah) {
                    $skipped++;
                    continue;
                }

                // Parse anomali codes (comma-separated)
                $anomaliRaw = trim($row[$anomaliIndex] ?? '');
                $anomaliCodes = array_filter(array_map('trim', explode(',', $anomaliRaw)));

                if (empty($anomaliCodes)) {
                    $skipped++;
                    continue;
                }

                // Insert/Update per anomali code (MERGE MODE)
                foreach ($anomaliCodes as $anomaliCode) {
                    AnomalyData::updateOrCreate(
                        [
                            'activity_id' => $activity->id,
                            'kode_daerah' => $kodeDaerah,
                            'dsrt' => $dsrt,
                            'no_art' => $noArt,
                            'anomali' => $anomaliCode, // Single anomali code per row
                        ],
                        [
                            'kecamatan' => $kecamatan,
                            'desa' => $desa,
                            'nama_krt' => $namaKrt,
                            'nama_art' => $namaArt,
                            'link' => !empty($link) ? $link : null,
                            'checked' => false, // Reset checked flag on update
                        ]
                    );
                    $inserted++;
                }
            }

            fclose($handle);

            // Update activity's last_data_upload_at
            $filename = $file->getClientOriginalName();
            $activity->update(['last_data_upload_at' => now()]);

            // Log upload history
            $activity->uploadHistories()->create([
                'uploaded_by' => auth()->id(),
                'file_type' => 'anomaly_csv',
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'file_size' => $file->getSize(),
                'records_imported' => $inserted,
                'status' => 'completed',
            ]);

            $message = "Anomaly CSV uploaded successfully! MERGE mode: Imported {$inserted} anomali records.";
            if ($skipped > 0) $message .= " Skipped {$skipped} rows.";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading Anomaly CSV: ' . $e->getMessage());
        }
    }

    /**
     * Upload Officer JSON file
     */
    public function uploadOfficerJson(Request $request, Activity $activity)
    {
        try {
            $request->validate([
                'officer_json_file' => 'required|file|mimes:json,txt|max:10240',
            ]);

            $file = $request->file('officer_json_file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
            }

            if (!is_array($data)) {
                throw new \Exception('JSON must be an array of objects');
            }

            $created = 0;
            $updated = 0;
            $deleted = 0;
            $skipped = 0;
            $processedCodes = [];
            $jsonVillageCodes = [];

            foreach ($data as $item) {
                // Build village code from components
                $provinsi = $item['Provinsi'] ?? '';
                $kabupaten = $item['Kabupaten/Kota'] ?? '';
                $kecamatan = $item['Kecamatan'] ?? '';
                $desa = $item['Desa/Kelurahan'] ?? '';

                $villageCode = $provinsi . $kabupaten . $kecamatan . $desa;

                // Validate village code length (should be 10 digits)
                if (strlen($villageCode) !== 10 || !ctype_digit($villageCode)) {
                    $skipped++;
                    continue;
                }

                $supervisorEmail = $item['Email Pengawas'] ?? null;
                $enumeratorEmail = $item['Email Pencacah'] ?? null;

                // Get target from PjMapping (primary source), fallback to MonitoringData metrics
                $pjMapping = $activity->pjMappings()
                    ->where('village_code', $villageCode)
                    ->first();

                $target = 0;

                // Primary source: pj_mappings.target (assigned target)
                if ($pjMapping && $pjMapping->target > 0) {
                    $target = $pjMapping->target;
                } else {
                    // Fallback: calculate from monitoring data metrics if available
                    $monitoringData = $activity->monitoringData()
                        ->where('village_code', $villageCode)
                        ->first();

                    if ($monitoringData) {
                        $target = $monitoringData->open + $monitoringData->submitted +
                                  $monitoringData->approved + $monitoringData->rejected;
                    }
                }

                // UPSERT
                $existing = $activity->officerMappings()
                    ->where('village_code', $villageCode)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'supervisor_email' => $supervisorEmail,
                        'enumerator_email' => $enumeratorEmail,
                        'target' => max($existing->target, $target),
                    ]);
                    $updated++;
                } else {
                    $activity->officerMappings()->create([
                        'village_code' => $villageCode,
                        'supervisor_email' => $supervisorEmail,
                        'enumerator_email' => $enumeratorEmail,
                        'target' => $target,
                    ]);
                    $created++;
                }

                $jsonVillageCodes[] = $villageCode;
            }

            // Delete orphaned mappings (SYNC mode)
            $toDelete = $activity->officerMappings()
                ->whereNotIn('village_code', $jsonVillageCodes)
                ->get();

            foreach ($toDelete as $officer) {
                $officer->delete();
                $deleted++;
            }

            // Update activity
            $filename = $file->getClientOriginalName();
            $activity->update([
                'officer_json_filename' => $filename,
                'last_data_upload_at' => now(),
            ]);

            // Log upload history
            $activity->uploadHistories()->create([
                'uploaded_by' => auth()->id(),
                'file_type' => 'officer_json',
                'original_filename' => $filename,
                'stored_filename' => $filename,
                'file_size' => $file->getSize(),
                'records_imported' => $created + $updated,
                'status' => 'completed',
            ]);

            $message = "Officer JSON uploaded successfully!";
            if ($created > 0) $message .= " Created {$created}.";
            if ($updated > 0) $message .= " Updated {$updated}.";
            if ($deleted > 0) $message .= " Deleted {$deleted}.";
            if ($skipped > 0) $message .= " Skipped {$skipped}.";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading Officer JSON: ' . $e->getMessage());
        }
    }

    /**
     * Recursively delete directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
