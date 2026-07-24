<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * HEMIS orqali kira olmaydigan (lekin HEMIS'da mavjud) hodimlar uchun
     * platforma paroli birinchi kirishda tug'ilgan kun orqali o'rnatiladi.
     * `login_activated_at` null bo'lsa — hodim hali parol o'rnatmagan
     * (birinchi kirish: tug'ilgan kun so'raladi). Aks holda oddiy parol.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->timestamp('login_activated_at')->nullable()->after('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('login_activated_at');
        });
    }
};
