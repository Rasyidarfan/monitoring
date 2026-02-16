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
        Schema::create('pj_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->onDelete('cascade');
            $table->string('village_code', 50);
            $table->string('pj_code', 50);
            $table->string('pj_name');
            $table->timestamps();

            $table->unique(['activity_id', 'village_code']);
            $table->index('activity_id');
            $table->index('pj_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pj_mappings');
    }
};
