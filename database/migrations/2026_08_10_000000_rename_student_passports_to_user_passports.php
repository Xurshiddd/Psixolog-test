<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Passport endi faqat talabalar uchun emas — xodimlar va ishga qabul
     * qilinmagan nomzodlar uchun ham tayyorlanadi. Shuning uchun jadval va
     * ustunlar role'ga bog'liq bo'lmagan nom bilan qayta nomlanadi.
     */
    public function up(): void
    {
        Schema::rename('student_passports', 'user_passports');

        Schema::table('user_passports', function (Blueprint $table) {
            $table->renameColumn('student_id', 'user_id');
            $table->renameColumn('student_conclusion', 'conclusion');
        });
    }

    public function down(): void
    {
        Schema::table('user_passports', function (Blueprint $table) {
            $table->renameColumn('user_id', 'student_id');
            $table->renameColumn('conclusion', 'student_conclusion');
        });

        Schema::rename('user_passports', 'student_passports');
    }
};
