<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\MonitoringData;
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
     * Upload JSON file (PJ Mapping)
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

            $updated = 0;
            foreach ($data as $item) {
                if (!isset($item['Id']) || !isset($item['PJ'])) {
                    continue;
                }

                // Update monitoring data with PJ info
                $villageCode = $item['Id'];
                $pjName = $item['PJ'];

                MonitoringData::where('activity_id', $activity->id)
                    ->where('village_code', $villageCode)
                    ->update([
                        'pj_code' => $villageCode,
                        'pj_name' => $pjName,
                    ]);

                $updated++;
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
                'records_imported' => $updated,
                'status' => 'completed',
            ]);

            return back()->with('success', "JSON uploaded successfully! Updated {$updated} PJ mappings.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading JSON: ' . $e->getMessage());
        }
    }

    /**
     * Upload CSV file (Monitoring Data)
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

                // Update or create
                $record = MonitoringData::updateOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'village_code' => $villageCode,
                    ],
                    [
                        'village_name' => $villageName,
                        'regency_code' => $regencyCode,
                        'target' => $values['target'],
                        'open' => $values['open'],
                        'submitted' => $values['submitted'],
                        'approved' => $values['approved'],
                        'rejected' => $values['rejected'],
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $inserted++;
                } else {
                    $updated++;
                }
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

            $message = "CSV uploaded successfully! Inserted: {$inserted}, Updated: {$updated}";
            if ($skipped > 0) {
                $message .= ", Skipped: {$skipped}";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading CSV: ' . $e->getMessage());
        }
    }

    /**
     * Upload ZIP file
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

                $record = MonitoringData::updateOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'village_code' => $villageCode,
                    ],
                    [
                        'village_name' => $villageName,
                        'regency_code' => $regencyCode,
                        'target' => $values['target'],
                        'open' => $values['open'],
                        'submitted' => $values['submitted'],
                        'approved' => $values['approved'],
                        'rejected' => $values['rejected'],
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $inserted++;
                } else {
                    $updated++;
                }
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

            $message = "ZIP uploaded successfully! Inserted: {$inserted}, Updated: {$updated}";
            if ($skipped > 0) {
                $message .= ", Skipped: {$skipped}";
            }

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
