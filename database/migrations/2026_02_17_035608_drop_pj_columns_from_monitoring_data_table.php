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
        Schema::table('monitoring_data', function (Blueprint $table) {
            $table->dropColumn(['pj_code', 'pj_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_data', function (Blueprint $table) {
            $table->string('pj_code', 50)->nullable();
            $table->string('pj_name')->nullable();
        });
    }
};
