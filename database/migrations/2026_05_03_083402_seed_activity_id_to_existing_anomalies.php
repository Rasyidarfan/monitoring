<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Distribute existing anomalies across all activities for backward compatibility
     */
    public function up(): void
    {
        $activities = DB::table('activities')->pluck('id')->toArray();

        if (empty($activities)) {
            return; // No activities, skip
        }

        // For each existing anomaly record without activity_id
        $anomalies = DB::table('anomalies')->whereNull('activity_id')->get();

        foreach ($anomalies as $anomaly) {
            // Assign to first activity (SNLIK - Pemutakhiran with id=1)
            // Users can upload per-activity master data later to override
            DB::table('anomalies')
                ->where('id', $anomaly->id)
                ->update(['activity_id' => $activities[0]]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('anomalies')->update(['activity_id' => null]);
    }
};
