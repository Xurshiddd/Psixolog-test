<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Modul qaysi auditoriya turlari uchun ekanini saqlaydi (multiselect):
     * student, employee, guest. null/bo'sh = cheklov yo'q.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->jsonb('audiences')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('audiences');
        });
    }
};
