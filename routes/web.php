<?php

use App\Http\Controllers\StudentController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\HemisAuthController;
use App\Models\Module;
use App\Models\Test;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    if (auth()->user()->role === 'student') {
        return Inertia::render("Student/Index", [
            'testsCount' => Test::count(),
            'modulesCount' => Module::count(),
            'modules' => Module::paginate(10),
        ]);
    }
    return Inertia::render('Dashboard', [
        'testsCount' => Test::count(),
        'modulesCount' => Module::count()
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
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/test/index', [TestController::class, 'index'])->name('test_index');
    Route::get('/test/create', [TestController::class, 'create'])->name('test_create');
    Route::post('/test/store', [TestController::class, 'store'])->name('test_store');
    Route::get('/test/edit/{id}', [TestController::class, 'edit'])->name('test_edit');
    Route::put('/test/update/{id}', [TestController::class, 'update'])->name('test_update');
    Route::patch('/module/change-status', [TestController::class, 'changeModuleStatus'])->name('module_change_status');
    Route::delete('/module/delete/{id}', [TestController::class, 'deleteModule'])->name('module_delete');
});
Route::middleware('auth')->group(function () {
    Route::get('/student/index', [StudentController::class, 'index'])->name('student_test_index');
    Route::get('/test/take/{moduleId}', [StudentController::class, 'takeTest'])->name('student_test_take');
    Route::post('/student/test/solve', [StudentController::class, 'submitTest'])->name('student_test_solve');
});

require __DIR__ . '/settings.php';
