<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ishga hali qabul qilinmaganlar (mehmon / nomzod) uchun qo'shimcha
     * ma'lumotlar. Asosiy ma'lumot `users` jadvalida saqlanadi.
     */
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('father_name')->nullable();
            $table->string('address')->nullable();
            $table->string('desired_position')->nullable();
            $table->string('application_status')->default('pending')->index();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
