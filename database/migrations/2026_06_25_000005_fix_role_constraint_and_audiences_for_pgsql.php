<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Postgres'da `role` CHECK constraint'iga employee/guest qo'shadi va
     * `modules.audiences` ni jsonb ga o'tkazadi (whereJsonContains uchun).
     * Avval json/eski constraint bilan migratsiya qilingan bazalar uchun.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['admin', 'psiholog', 'student', 'employee', 'guest']::text[]))");

            DB::statement('ALTER TABLE modules ALTER COLUMN audiences TYPE jsonb USING audiences::jsonb');
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'psiholog', 'student', 'employee', 'guest') NOT NULL DEFAULT 'student'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE modules ALTER COLUMN audiences TYPE json USING audiences::json');
        }

        // role constraint 000003 migratsiyasi tomonidan boshqariladi.
    }
};
