<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\MonitoringData;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Activity 1: Monitoring Dashboard 2024
        $activity1 = Activity::create([
            'name' => 'monitoring-dashboard-2024',
            'display_name' => 'Monitoring Dashboard 2024',
            'description' => 'Dashboard monitoring kegiatan tahun 2024',
            'last_data_upload_at' => now()->subDays(2),
        ]);

        $this->seedVillagesForActivity($activity1, [
            // Jayawijaya (9702)
            ['9702010001', 'WAMENA', '9702', 50, 10, 15, 20, 5, 'PJ001', 'Ibu Farah'],
            ['9702010002', 'SINAKMA', '9702', 45, 8, 12, 22, 3, 'PJ001', 'Ibu Farah'],
            ['9702010003', 'WAMENA KOTA', '9702', 60, 12, 18, 25, 5, 'PJ002', 'Rayhan'],
            ['9702020001', 'HUBIKOSI', '9702', 40, 9, 11, 17, 3, 'PJ002', 'Rayhan'],
            ['9702020002', 'PIRAMID', '9702', 35, 7, 10, 15, 3, 'PJ003', 'Ahmad'],

            // Mamberamo Tengah (9705)
            ['9705010001', 'KOBAKMA', '9705', 38, 8, 12, 15, 3, 'PJ004', 'Siti'],
            ['9705010002', 'ILUGWA', '9705', 42, 10, 13, 16, 3, 'PJ004', 'Siti'],
            ['9705020001', 'MEGAMBILIS', '9705', 36, 7, 11, 15, 3, 'PJ005', 'Budi'],
            ['9705020002', 'ERAGAYAM', '9705', 40, 9, 12, 16, 3, 'PJ005', 'Budi'],

            // Yalimo (9706)
            ['9706010001', 'ELELIM', '9706', 45, 10, 14, 18, 3, 'PJ006', 'Dewi'],
            ['9706010002', 'PRONGGOLI', '9706', 48, 11, 15, 19, 3, 'PJ006', 'Dewi'],
            ['9706020001', 'APALAPSILI', '9706', 42, 9, 13, 17, 3, 'PJ007', 'Hendra'],
            ['9706020002', 'WELAREK', '9706', 40, 8, 12, 17, 3, 'PJ007', 'Hendra'],
        ]);

        // Activity 2: Survey BDT 2024
        $activity2 = Activity::create([
            'name' => 'survey-bdt-2024',
            'display_name' => 'Survey BDT 2024',
            'description' => 'Survei Basis Data Terpadu untuk Program Bansos',
            'last_data_upload_at' => now()->subDays(5),
        ]);

        $this->seedVillagesForActivity($activity2, [
            // Jayawijaya (9702)
            ['9702010001', 'WAMENA', '9702', 120, 30, 40, 45, 5, 'PJ101', 'Rina'],
            ['9702010002', 'SINAKMA', '9702', 100, 25, 35, 35, 5, 'PJ101', 'Rina'],
            ['9702010003', 'WAMENA KOTA', '9702', 150, 40, 50, 55, 5, 'PJ102', 'Joko'],
            ['9702020001', 'HUBIKOSI', '9702', 90, 20, 30, 35, 5, 'PJ102', 'Joko'],

            // Mamberamo Tengah (9705)
            ['9705010001', 'KOBAKMA', '9705', 85, 20, 28, 32, 5, 'PJ103', 'Ani'],
            ['9705010002', 'ILUGWA', '9705', 95, 22, 30, 38, 5, 'PJ103', 'Ani'],

            // Yalimo (9706)
            ['9706010001', 'ELELIM', '9706', 110, 28, 35, 42, 5, 'PJ104', 'Tono'],
            ['9706010002', 'PRONGGOLI', '9706', 105, 26, 33, 40, 6, 'PJ104', 'Tono'],
        ]);

        // Activity 3: PODES 2024
        $activity3 = Activity::create([
            'name' => 'podes-2024',
            'display_name' => 'PODES 2024',
            'description' => 'Pendataan Potensi Desa',
            'last_data_upload_at' => now()->subHours(12),
        ]);

        $this->seedVillagesForActivity($activity3, [
            // Jayawijaya (9702)
            ['9702010001', 'WAMENA', '9702', 25, 5, 8, 10, 2, 'PJ201', 'Lina'],
            ['9702010002', 'SINAKMA', '9702', 22, 4, 7, 9, 2, 'PJ201', 'Lina'],
            ['9702010003', 'WAMENA KOTA', '9702', 28, 6, 9, 11, 2, 'PJ202', 'Dedi'],
            ['9702020001', 'HUBIKOSI', '9702', 20, 4, 6, 8, 2, 'PJ202', 'Dedi'],
            ['9702020002', 'PIRAMID', '9702', 18, 3, 5, 8, 2, 'PJ203', 'Maya'],

            // Mamberamo Tengah (9705)
            ['9705010001', 'KOBAKMA', '9705', 19, 3, 6, 8, 2, 'PJ204', 'Rudi'],
            ['9705010002', 'ILUGWA', '9705', 21, 4, 7, 8, 2, 'PJ204', 'Rudi'],
            ['9705020001', 'MEGAMBILIS', '9705', 18, 3, 5, 8, 2, 'PJ205', 'Sari'],
            ['9705020002', 'ERAGAYAM', '9705', 20, 4, 6, 8, 2, 'PJ205', 'Sari'],

            // Yalimo (9706)
            ['9706010001', 'ELELIM', '9706', 23, 5, 7, 9, 2, 'PJ206', 'Eko'],
            ['9706010002', 'PRONGGOLI', '9706', 24, 5, 8, 9, 2, 'PJ206', 'Eko'],
            ['9706020001', 'APALAPSILI', '9706', 21, 4, 7, 8, 2, 'PJ207', 'Nina'],
            ['9706020002', 'WELAREK', '9706', 20, 4, 6, 8, 2, 'PJ207', 'Nina'],
        ]);

        $this->command->info('✅ Demo data created successfully!');
        $this->command->info('   - 3 Activities created');
        $this->command->info('   - Total villages: ' . MonitoringData::count());
    }

    /**
     * Seed villages for a specific activity
     */
    private function seedVillagesForActivity(Activity $activity, array $villages): void
    {
        foreach ($villages as $village) {
            MonitoringData::create([
                'activity_id' => $activity->id,
                'village_code' => $village[0],
                'village_name' => $village[1],
                'regency_code' => $village[2],
                'target' => $village[3],
                'open' => $village[4],
                'submitted' => $village[5],
                'approved' => $village[6],
                'rejected' => $village[7],
                'pj_code' => $village[8] ?? null,
                'pj_name' => $village[9] ?? null,
            ]);
        }
    }
}
