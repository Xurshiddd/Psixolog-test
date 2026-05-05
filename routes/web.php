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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ActivityLogController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');
Route::fallback(function () {
    return Inertia::render('Error404')->toResponse(request())->setStatusCode(404);
});
Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('dashboard/module-score-report/export', [DashboardController::class, 'exportModuleScoreReport'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.module-score-report.export');

Route::get('/hemis/redirect', [HemisAuthController::class , 'redirectToHemis'])->name('hemis_redirect');
Route::get('/hemis/callback', [HemisAuthController::class , 'login'])->name('hemis.callback');
Route::post('/hemis/login', [HemisAuthController::class, 'passwordLogin'])->middleware('guest')->name('hemis.password-login');
Route::get('/locale/{locale}', function (string $locale) {
    $allowed = ['uz', 'ru'];
    if (!in_array($locale, $allowed, true))
        $locale = 'uz';

    return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->name('locale.switch');
Route::middleware(['auth', 'double'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/test/index', [TestController::class , 'index'])->name('test_index');
    Route::get('/test/create', [TestController::class , 'create'])->name('test_create');
    Route::post('/test/store', [TestController::class , 'store'])->name('test_store');
    Route::get('/test/edit/{id}', [TestController::class , 'edit'])->name('test_edit');
    Route::put('/test/update/{id}', [TestController::class , 'update'])->name('test_update');
    Route::patch('/module/change-status', [TestController::class , 'changeModuleStatus'])->name('module_change_status');
    Route::delete('/module/delete/{id}', [TestController::class , 'deleteModule'])->name('module_delete');

    Route::get('/admin/students', [AdminStudentController::class , 'index'])->name('admin.students.index');
    Route::get('/admin/students/export/excel', [AdminStudentController::class , 'exportExcel'])->name('admin.students.export.excel');
    Route::get('/admin/students/export/excel-with-diagnosis', [AdminStudentController::class , 'exportExcelWithDiagnosis'])->name('admin.students.export.excel-with-diagnosis');
    Route::get('/admin/students/export/pdf', [AdminStudentController::class , 'exportPdf'])->name('admin.students.export.pdf');
    Route::get('/admin/students/{user}', [AdminStudentController::class , 'show'])->name('admin.students.show');
    Route::get('/admin/students/{user}/passport/pdf', [AdminStudentController::class, 'downloadSavedStudentPassportPdf'])->name('admin.students.passport.download');
    Route::post('/admin/students/{user}/passport/pdf', [AdminStudentController::class, 'exportStudentPassportPdf'])->name('admin.students.passport.pdf');
    Route::get('/admin/students/{user}/results/{module}', [AdminStudentController::class , 'showResult'])->name('admin.students.results.show');
    Route::post('/admin/students/{user}/results/{module}/diagnosis', [AdminStudentController::class , 'updateDiagnosis'])->name('admin.students.results.diagnosis');
    Route::post('/admin/students/{user}/results/{module}/ai-diagnosis', [AdminStudentController::class , 'generateAiDiagnosis'])->name('admin.students.results.ai-diagnosis');
    Route::post('/admin/students/{user}/results/{module}/ai-diagnosis-stream', [AdminStudentController::class , 'streamAiDiagnosis'])->name('admin.students.results.ai-diagnosis-stream');
    Route::delete('/admin/students/{student}/results/{result}', [AdminStudentController::class, 'destroyResult'])->name('admin.students.results.destroy');
    Route::resource('result-categories', ResultCategoryController::class);
    Route::resource('categories', CategoryController::class);
    Route::post('/admin/students/{user}/sync-categories', [AdminStudentController::class, 'syncCategories'])->name('admin.students.sync-categories');
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
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    Route::prefix('psiholog')->name('psiholog.')->middleware('psiholog')->group(function () {
        Route::get('/requests', [RequestsController::class, 'psihologIndex'])->name('requests.index');
        Route::post('/requests/{conversation}/messages', [MessagesController::class, 'psihologStore'])->name('requests.messages.store');
    });

});
require __DIR__ . '/settings.php';
