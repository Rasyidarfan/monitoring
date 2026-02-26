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
        Schema::create('anomaly_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('kode_daerah', 50);
            $table->string('kecamatan');
            $table->string('desa');
            $table->string('dsrt', 10); // Nomor rumah tangga/KK
            $table->integer('no_art'); // Nomor urut ART
            $table->string('nama_krt'); // Nama Kepala Rumah Tangga
            $table->string('nama_art'); // Nama ART
            $table->text('link')->nullable(); // Link FASIH
            $table->string('anomali', 500); // Kode anomali (space-separated)
            $table->timestamps();

            // Indexes untuk filtering dan grouping
            $table->index(['activity_id', 'kode_daerah', 'dsrt']);
            $table->index(['activity_id', 'kecamatan']);
            $table->index(['activity_id', 'desa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomaly_data');
    }
};
