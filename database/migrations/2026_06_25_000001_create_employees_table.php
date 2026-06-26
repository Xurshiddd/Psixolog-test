<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Hodim (employee) uchun qo'shimcha ma'lumotlar. Asosiy ma'lumot `users`
     * jadvalida saqlanadi, hodimga xos qismi shu yerda.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('employee_id_number')->nullable()->index();
            $table->json('staff_position')->nullable();
            $table->string('employee_type_name')->nullable();
            $table->string('department_name')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
