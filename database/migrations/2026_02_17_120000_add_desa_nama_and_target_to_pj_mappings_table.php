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
        Schema::table('pj_mappings', function (Blueprint $table) {
            $table->string('desa_nama')->nullable()->after('village_code');
            $table->integer('target')->default(0)->after('pj_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pj_mappings', function (Blueprint $table) {
            $table->dropColumn(['desa_nama', 'target']);
        });
    }
};
