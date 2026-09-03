<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Talaba qiziqishlari (hobby). Bitta talabada bir nechta qiziqish bo'ladi;
     * passportda "Xarakterdagi qobiliyatlar" o'rniga shular chiqadi.
     */
    public function up(): void
    {
        Schema::create('hobbies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            // Bir xil qiziqish ikki marta qo'shilmasin.
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hobbies');
    }
};
