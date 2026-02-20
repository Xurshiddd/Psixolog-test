<?php

use App\Http\Controllers\StudentController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\HemisAuthController;
use App\Models\Module;
use App\Models\Test;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\Student\ConversationController;
use App\Http\Controllers\Student\MessageController;
use App\Http\Controllers\Staff\RequestsController;
use App\Http\Controllers\Staff\MessagesController;
use App\Http\Controllers\ResultCategoryController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');
Route::fallback(function () {
    return Inertia::render('Error404')->toResponse(request())->setStatusCode(404);
});
Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/hemis/redirect', [HemisAuthController::class , 'redirectToHemis'])->name('hemis_redirect');
Route::get('/hemis/callback', [HemisAuthController::class , 'login'])->name('hemis.callback');
Route::get('/locale/{locale}', function (string $locale) {
    $allowed = ['uz', 'ru'];
    if (!in_array($locale, $allowed, true))
        $locale = 'uz';

    return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->name('locale.switch');
Route::middleware(['auth', 'double'])->group(function () {
    Route::get('/test/index', [TestController::class , 'index'])->name('test_index');
    Route::get('/test/create', [TestController::class , 'create'])->name('test_create');
    Route::post('/test/store', [TestController::class , 'store'])->name('test_store');
    Route::get('/test/edit/{id}', [TestController::class , 'edit'])->name('test_edit');
    Route::put('/test/update/{id}', [TestController::class , 'update'])->name('test_update');
    Route::patch('/module/change-status', [TestController::class , 'changeModuleStatus'])->name('module_change_status');
    Route::delete('/module/delete/{id}', [TestController::class , 'deleteModule'])->name('module_delete');

    Route::get('/admin/students', [AdminStudentController::class , 'index'])->name('admin.students.index');
    Route::get('/admin/students/export/excel', [AdminStudentController::class , 'exportExcel'])->name('admin.students.export.excel');
    Route::get('/admin/students/export/pdf', [AdminStudentController::class , 'exportPdf'])->name('admin.students.export.pdf');
    Route::get('/admin/students/{user}', [AdminStudentController::class , 'show'])->name('admin.students.show');
    Route::get('/admin/students/{user}/results/{module}', [AdminStudentController::class , 'showResult'])->name('admin.students.results.show');
    Route::post('/admin/students/{user}/results/{module}/diagnosis', [AdminStudentController::class , 'updateDiagnosis'])->name('admin.students.results.diagnosis');
    Route::resource('result-categories', ResultCategoryController::class);
    Route::get('/api/modules/{module}/test-options', [ResultCategoryController::class, 'getModuleTestOptions'])->name('module.test-options');
});
Route::middleware(['auth', 'student'])->group(function () {
    Route::get('/student/index', [StudentController::class , 'index'])->name('student_test_index');
    Route::get('/test/take/{moduleId}', [StudentController::class , 'takeTest'])->name('student_test_take');
    Route::post('/student/test/solve', [StudentController::class , 'submitTest'])->name('student_test_solve');
});
Route::middleware(['auth'])->prefix('student')->name('student.')->group(function () {
    Route::get('/requests', [ConversationController::class, 'index'])->name('requests.index');
    Route::post('/requests', [ConversationController::class, 'store'])->name('requests.store');

    Route::post('/requests/{conversation}/messages', [MessageController::class, 'store'])
        ->name('requests.messages.store');
});
Route::middleware(['auth'])->group(function () {

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/requests', [RequestsController::class, 'adminIndex'])->name('requests.index');
        Route::post('/requests/{conversation}/messages', [MessagesController::class, 'adminStore'])->name('requests.messages.store');
    });

    Route::prefix('psiholog')->name('psiholog.')->middleware('psiholog')->group(function () {
        Route::get('/requests', [RequestsController::class, 'psihologIndex'])->name('requests.index');
        Route::post('/requests/{conversation}/messages', [MessagesController::class, 'psihologStore'])->name('requests.messages.store');
    });

});
require __DIR__ . '/settings.php';
