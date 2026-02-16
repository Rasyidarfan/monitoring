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
        Schema::create('monitoring_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');

            // Identifikasi desa
            $table->string('village_code', 50);
            $table->string('village_name');
            $table->string('regency_code', 10);

            // Metrics
            $table->integer('target')->default(0);
            $table->integer('open')->default(0);
            $table->integer('submitted')->default(0);
            $table->integer('approved')->default(0);
            $table->integer('rejected')->default(0);

            // PJ mapping
            $table->string('pj_code', 50)->nullable();
            $table->string('pj_name')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('activity_id');
            $table->index('regency_code');
            $table->index('village_code');
            $table->index('pj_code');
            $table->index(['activity_id', 'village_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_data');
    }
};
