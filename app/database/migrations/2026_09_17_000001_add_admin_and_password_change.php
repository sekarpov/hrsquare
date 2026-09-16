<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('ADMIN', 'RECRUITER', 'MANAGER'))");
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('auth_version')->default(0);
        });
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', 'ADMIN')->exists()) {
            throw new RuntimeException('Reassign administrator roles before rolling back this migration.');
        }
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('RECRUITER', 'MANAGER'))");
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['must_change_password', 'auth_version']));
    }
};
