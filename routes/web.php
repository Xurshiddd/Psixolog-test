<?php

use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\HemisAuthController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard', [
        'testsCount' => \App\Models\Test::count(),
        'modulesCount' => \App\Models\Module::count()
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/hemis/redirect', [HemisAuthController::class, 'redirectToHemis'])->name('hemis_redirect');
Route::get('/hemis/callback', [HemisAuthController::class, 'login'])->name('hemis.callback');
Route::get('/locale/{locale}', function (string $locale) {
    $allowed = ['uz', 'ru'];
    if (!in_array($locale, $allowed, true))
        $locale = 'uz';

    return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->name('locale.switch');
Route::middleware('auth')->group(function () {
    Route::get('/test/index', [TestController::class, 'index'])->name('test_index');
    Route::get('/test/create', [TestController::class, 'create'])->name('test_create');
    Route::post('/test/store', [TestController::class, 'store'])->name('test_store');
    Route::get('/test/edit/{id}', [TestController::class, 'edit'])->name('test_edit');
    Route::put('/test/update/{id}', [TestController::class, 'update'])->name('test_update');
    Route::patch('/module/change-status', [TestController::class, 'changeModuleStatus'])->name('module_change_status');
    Route::delete('/module/delete/{id}', [TestController::class, 'deleteModule'])->name('module_delete');
});
require __DIR__ . '/settings.php';
