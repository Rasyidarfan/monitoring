<?php

namespace Database\Seeders;

use App\Models\Anomaly;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AnomalySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Read JSON file
        $jsonPath = public_path('tableConvert.com_m9zuav.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("JSON file not found: {$jsonPath}");
            return;
        }

        $jsonContent = File::get($jsonPath);
        $anomalies = json_decode($jsonContent, true);

        if (!is_array($anomalies)) {
            $this->command->error("Invalid JSON format");
            return;
        }

        // Clear existing anomalies
        Anomaly::truncate();

        // Insert anomalies
        foreach ($anomalies as $item) {
            $code = $item['No. Anomali'] ?? null;
            $rule = $item['Rule dg Nama Variable di FASIH'] ?? null;
            $description = $item['Keterangan (disesuaikan dg variabel FASIH)'] ?? null;

            if ($code && $description) {
                Anomaly::create([
                    'code' => $code,
                    'rule' => $rule,
                    'description' => $description,
                ]);
            }
        }

        $this->command->info("Anomalies seeded successfully!");
    }
}
