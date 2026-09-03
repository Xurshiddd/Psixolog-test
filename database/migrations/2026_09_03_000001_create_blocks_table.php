<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blok — modullardan katta birlik. Blokka biriktirilgan modullar
     * `position` bo'yicha ketma-ket joylashadi va talaba ularni shu
     * tartibda yechishga majbur bo'ladi.
     */
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('block_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // Bitta modul faqat bitta blokka tegishli bo'ladi — aks holda
            // ketma-ketlik talabi ikki xil blokda ziddiyatga olib keladi.
            $table->unique('module_id');
            $table->index(['block_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_module');
        Schema::dropIfExists('blocks');
    }
};
