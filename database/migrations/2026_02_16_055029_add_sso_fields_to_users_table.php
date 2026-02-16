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
        Schema::table('users', function (Blueprint $table) {
            // SSO User Data
            $table->string('sso_user_id')->unique()->nullable()->after('id');
            $table->string('nip_9', 9)->unique()->nullable()->after('sso_user_id');
            $table->string('nip_18', 18)->unique()->nullable()->after('nip_9');

            // Make email and password nullable (SSO provides auth)
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            // Store roles as JSON
            $table->json('sso_roles')->nullable()->after('remember_token');

            // Last SSO sync timestamp
            $table->timestamp('last_sso_sync_at')->nullable();

            // Indexes
            $table->index('sso_user_id');
            $table->index('nip_9');
            $table->index('nip_18');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['sso_user_id']);
            $table->dropIndex(['nip_9']);
            $table->dropIndex(['nip_18']);

            $table->dropColumn([
                'sso_user_id',
                'nip_9',
                'nip_18',
                'sso_roles',
                'last_sso_sync_at',
            ]);
        });
    }
};
