<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zudlik bilan ish olib borish kerak bo'lgan holatlar: talaba xavfli deb
     * belgilangan variantni tanlaganda yoziladi. Psixolog "Hal qilindi"
     * bosganda `resolved_at` to'ldiriladi va sidebardagi qizil belgi kamayadi.
     */
    public function up(): void
    {
        Schema::create('critical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('test_option_id')->constrained('test_options')->cascadeOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Bir xil javob uchun ikkinchi ogohlantirish yozilmasin.
            $table->unique(['user_id', 'test_id', 'test_option_id'], 'critical_alerts_answer_unique');
            $table->index(['resolved_at', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_alerts');
    }
};
