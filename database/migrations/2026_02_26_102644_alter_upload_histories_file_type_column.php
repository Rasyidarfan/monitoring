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
        Schema::table('upload_histories', function (Blueprint $table) {
            $table->string('file_type', 20)->change(); // Increase length to accommodate 'anomaly_csv'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_histories', function (Blueprint $table) {
            $table->string('file_type')->change();
        });
    }
};
