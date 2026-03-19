<?php

use App\Http\Controllers\Api\Student\AuthController;
use App\Http\Controllers\Api\Student\ConversationController;
use App\Http\Controllers\Api\Student\ModuleController;
use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('api.student.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('student.api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/{module}', [ModuleController::class, 'show'])->name('modules.show');
        Route::post('/modules/{module}/submit', [ModuleController::class, 'submit'])->name('modules.submit');
        Route::get('/results', [ModuleController::class, 'results'])->name('results.index');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->name('conversations.messages.store');
    });
});
