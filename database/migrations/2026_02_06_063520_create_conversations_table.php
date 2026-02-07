<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
    Schema::create('conversations', function (Blueprint $table) {
      $table->id();
     $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // "admin" yoki "psychologist"
            $table->string('channel', 20);

            // keyinchalik biriktiriladigan staff user (admin/psixolog)
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('subject')->nullable(); // optional
            $table->string('status', 20)->default('open'); // open/closed

            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'channel']);
            $table->index(['status', 'last_message_at']);
    });

    Schema::create('conversation_user', function (Blueprint $table) {
      $table->id();
      $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->timestamp('last_read_at')->nullable();
      $table->unique(['conversation_id', 'user_id']);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('conversation_user');
    Schema::dropIfExists('conversations');
  }
};
