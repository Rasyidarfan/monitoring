<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            // Add activity_id for per-activity master data
            $table->unsignedBigInteger('activity_id')->nullable()->after('id');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');

            // Drop existing unique constraint on code only
            $table->dropUnique('anomalies_code_unique');

            // Add new unique constraint: code per activity (or global if activity_id is null)
            $table->unique(['activity_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropIndex('anomalies_activity_id_code_unique');
            $table->dropColumn('activity_id');
            $table->unique('code');
        });
    }
};
