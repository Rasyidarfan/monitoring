<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // Populate target from monitoring_data: sum of open + submitted + approved + rejected
        DB::statement('
            UPDATE pj_mappings pm
            SET pm.target = COALESCE((
                SELECT (COALESCE(md.open, 0) + COALESCE(md.submitted, 0) + COALESCE(md.approved, 0) + COALESCE(md.rejected, 0))
                FROM monitoring_data md
                WHERE md.village_code = pm.village_code
                AND md.activity_id = pm.activity_id
                LIMIT 1
            ), 0)
        ');
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
