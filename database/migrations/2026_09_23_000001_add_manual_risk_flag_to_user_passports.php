<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_passports', function (Blueprint $table) {
            $table->string('manual_risk_flag')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_passports', function (Blueprint $table) {
            $table->dropColumn('manual_risk_flag');
        });
    }
};
