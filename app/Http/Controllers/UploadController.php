<?php

namespace App\Http\Controllers;

use App\Models\Activity;
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
     * Upload CSV file (Monitoring Data) - SYNC Mode with MAX Logic
     * Keep existing pj_mappings, update desa_nama and target (if new target is larger)
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

                // Insert monitoring data (without target)
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
     * Upload ZIP file - REPLACE Mode with MAX Logic
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

            // Look for query_1.csv (check both root and subdirectory)
            $csvFile = null;
            if (file_exists($extractPath . '/query_1.csv')) {
                $csvFile = $extractPath . '/query_1.csv';
            } else {
                // Check subdirectories
                $files = glob($extractPath . '/*/query_1.csv');
                if (!empty($files)) {
                    $csvFile = $files[0];
                }
            }

            if (!$csvFile || !file_exists($csvFile)) {
                // Clean up
                $this->deleteDirectory($extractPath);
                throw new \Exception('query_1.csv not found in ZIP file');
            }

            // SYNC Mode: Clear monitoring data, keep pj_mappings
            MonitoringData::where('activity_id', $activity->id)->delete();

            // Process CSV with flexible mapping
            $csvMapper = new CsvMappingService();
            $handle = fopen($csvFile, 'r');
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

                // Insert monitoring data (without target)
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
            if ($inserted > 0) $message .= " Imported: {$inserted}.";
            if ($updated > 0) $message .= " Updated: {$updated}.";
            if ($skipped > 0) $message .= " Skipped: {$skipped} (target=0 or invalid).";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading ZIP: ' . $e->getMessage());
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
