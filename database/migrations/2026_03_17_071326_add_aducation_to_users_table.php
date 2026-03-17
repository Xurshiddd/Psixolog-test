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
            $table->integer('education_type_code')->nullable()->index();
            $table->string('education_type_name')->nullable();
            $table->integer('education_form_code')->nullable()->index();
            $table->string('education_form_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['education_type_code']);
            $table->dropIndex(['education_form_code']);
            $table->dropColumn('education_type_code');
            $table->dropColumn('education_type_name');
            $table->dropColumn('education_form_code');
            $table->dropColumn('education_form_name');
        });
    }
};
