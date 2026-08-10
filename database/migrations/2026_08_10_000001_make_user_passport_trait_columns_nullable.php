<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomzod passportida faqat xulosa to'ldiriladi — qobiliyatlar ketma-ketligi
     * va temperament tipi umuman so'ralmaydi, shuning uchun bu ustunlar
     * bo'sh qolishi mumkin.
     */
    public function up(): void
    {
        Schema::table('user_passports', function (Blueprint $table) {
            $table->json('character_traits')->nullable()->change();
            $table->text('temperament_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_passports', function (Blueprint $table) {
            $table->json('character_traits')->nullable(false)->change();
            $table->text('temperament_type')->nullable(false)->change();
        });
    }
};
