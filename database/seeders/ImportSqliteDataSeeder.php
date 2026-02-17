<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\MonitoringData;
use Illuminate\Database\Seeder;
use PDO;

class ImportSqliteDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sqlitePath = public_path('monitoring_data.db');

        if (!file_exists($sqlitePath)) {
            $this->command->error("SQLite database not found at: {$sqlitePath}");
            return;
        }

        // Connect to SQLite
        $pdo = new PDO("sqlite:{$sqlitePath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get activities from SQLite
        $activities = $pdo->query("SELECT * FROM activities")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($activities as $sqliteActivity) {
            // Create activity in MySQL
            $activity = Activity::create([
                'name' => $sqliteActivity['name'],
                'display_name' => $sqliteActivity['display_name'],
                'description' => null,
                'last_data_upload_at' => $sqliteActivity['last_updated'],
                'json_filename' => $sqliteActivity['json_filename'],
                'zip_filename' => $sqliteActivity['zip_filename'],
            ]);

            $this->command->info("Created activity: {$activity->display_name}");

            // Import monitoring data
            $tableName = 'monitoring_data_' . $sqliteActivity['id'];
            $pjTableName = 'pj_mapping_' . $sqliteActivity['id'];

            // Get PJ mapping
            $pjMapping = [];
            try {
                $pjData = $pdo->query("SELECT * FROM {$pjTableName}")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($pjData as $pj) {
                    $pjMapping[$pj['kode_id']] = $pj['PJ'];
                }
            } catch (\Exception $e) {
                $this->command->warn("No PJ mapping found for {$tableName}");
            }

            // Get monitoring data
            try {
                $monitoringData = $pdo->query("SELECT * FROM {$tableName}")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($monitoringData as $data) {
                    $villageCode = $data['kode_desa'];
                    $pjName = $pjMapping[$villageCode] ?? null;

                    MonitoringData::create([
                        'activity_id' => $activity->id,
                        'village_code' => $villageCode,
                        'village_name' => $data['nama_desa'],
                        'regency_code' => $data['kode_kabupaten'],
                        'target' => $data['Target'] ?? 0,
                        'open' => $data['OPEN'] ?? 0,
                        'submitted' => $data['SUBMITTED'] ?? 0,
                        'approved' => $data['APPROVED'] ?? 0,
                        'rejected' => $data['REJECTED'] ?? 0,
                    ]);

                    // Create PJ mapping separately
                    if ($pjName) {
                        $activity->pjMappings()->create([
                            'village_code' => $villageCode,
                            'pj_code' => $villageCode,
                            'pj_name' => $pjName,
                        ]);
                    }
                }

                $this->command->info("   - Imported " . count($monitoringData) . " villages");
            } catch (\Exception $e) {
                $this->command->error("Error importing {$tableName}: " . $e->getMessage());
            }
        }

        $this->command->info("\n✅ SQLite data imported successfully!");
        $this->command->info("   - Total activities: " . Activity::count());
        $this->command->info("   - Total villages: " . MonitoringData::count());
    }
}
