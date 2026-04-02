<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solve_tests', function (Blueprint $table) {
            $table->index(['module_id', 'user_id'], 'solve_tests_module_user_index');
        });

        Schema::table('users_tests_results', function (Blueprint $table) {
            $table->index(['module_id', 'user_id'], 'users_tests_results_module_user_index');
        });
    }

    public function down(): void
    {
        Schema::table('solve_tests', function (Blueprint $table) {
            $table->dropIndex('solve_tests_module_user_index');
        });

        Schema::table('users_tests_results', function (Blueprint $table) {
            $table->dropIndex('users_tests_results_module_user_index');
        });
    }
};
