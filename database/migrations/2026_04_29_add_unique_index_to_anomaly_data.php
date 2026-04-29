<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add unique index to prevent duplicate anomali records per activity+desa+dsrt+noart
     * Supports MERGE mode for anomaly CSV upload
     */
    public function up(): void
    {
        Schema::table('anomaly_data', function (Blueprint $table) {
            $table->unique(
                ['activity_id', 'kode_daerah', 'dsrt', 'no_art', 'anomali'],
                'anomaly_data_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anomaly_data', function (Blueprint $table) {
            $table->dropUnique('anomaly_data_unique');
        });
    }
};
