<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bayroq (xavf darajasi): qizil / sariq / yashil. Dashboarddagi
     * "Modul bo'yicha ball oralig'i natijalari" bo'limida avtomatik xulosa
     * bilan birga biriktiriladi.
     */
    public function up(): void
    {
        Schema::table('users_tests_results', function (Blueprint $table) {
            $table->enum('flag', ['red', 'yellow', 'green'])->nullable()->after('diagnosis');
            $table->index(['flag']);
        });
    }

    public function down(): void
    {
        Schema::table('users_tests_results', function (Blueprint $table) {
            $table->dropIndex(['flag']);
            $table->dropColumn('flag');
        });
    }
};
