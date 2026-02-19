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
        Schema::table('users_tests_results', function (Blueprint $table) {
            if (!Schema::hasColumn('users_tests_results', 'diagnosis')) {
                $table->text('diagnosis')->nullable()->after('result_real');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_tests_results', function (Blueprint $table) {
            if (Schema::hasColumn('users_tests_results', 'diagnosis')) {
                $table->dropColumn('diagnosis');
            }
        });
    }
};
