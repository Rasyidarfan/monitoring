<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks which individual anomaly codes are checked
     * Allows preserving check status when CSV data is updated
     */
    public function up(): void
    {
        Schema::create('anomaly_code_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anomaly_data_id');
            $table->string('code', 10); // e.g., "A01", "A75"
            $table->boolean('checked')->default(false);
            $table->timestamps();

            // Foreign key to anomaly_data
            $table->foreign('anomaly_data_id')->references('id')->on('anomaly_data')->onDelete('cascade');

            // Unique constraint: each code checked once per anomaly_data record
            $table->unique(['anomaly_data_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomaly_code_checks');
    }
};
