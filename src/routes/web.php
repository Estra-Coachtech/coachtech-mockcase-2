<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\AdminStatusMiddleware;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Illuminate\Http\Request;
use App\Http\Requests\CorrectionRequest;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [UserController::class, 'index']);
    Route::post('/attendance', [UserController::class, 'attendance']);
    Route::get('/attendance/list', [UserController::class, 'list']);
    Route::get('/application/{id}', [UserController::class, 'applicationDetail']);

    // ★ マイ勤怠レポート（応用）
    Route::get('/attendance/report', [ReportController::class, 'index'])->name('attendance.report');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/attendance/list', [AdminController::class, 'list']);
    Route::get('/admin/staff/list', [AdminController::class, 'staffList']);
    Route::get('/admin/attendance/staff/{id}', [AdminController::class, 'staffDetailList']);
    Route::post('/admin/logout', [AuthController::class, 'adminLogout']);
    Route::get('/stamp_correction_request/approve/{id}', [AdminController::class, 'approvalShow']);
    Route::post('/stamp_correction_request/approve/{id}', [AdminController::class, 'approval']);
    Route::post('/export', [AdminController::class, 'export']);
});

Route::middleware(['auth', AdminStatusMiddleware::class])->group(function () {
    // 管理者か一般ユーザーかは referer ではなくログインユーザーの admin_status で判定する。
    // （管理者の勤怠詳細 URL も /attendance/{id} 共有のため、referer 判定では管理者操作が
    //   一般ユーザー側コントローラに流れてしまう不具合があった）
    Route::get('/stamp_correction_request/list', function (Request $request) {
        if (auth()->user()->admin_status) {
            return app(AdminController::class)->applicationList();
        }
        return app(UserController::class)->applicationList();
    });
    Route::get('/attendance/{id}', function ($id, Request $request) {
        if (auth()->user()->admin_status) {
            return app(AdminController::class)->detail($id);
        }
        return app(UserController::class)->detail($id);
    });
    Route::post('/attendance/{id}', function (CorrectionRequest $request, $id) {
        if (auth()->user()->admin_status) {
            return app(AdminController::class)->amendmentApplication($request, $id);
        }
        return app(UserController::class)->amendmentApplication($request, $id);
    });
});

Route::get('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/admin/login', [AuthController::class, 'adminDoLogin']);

Route::post('/login', [AuthController::class, 'doLogin']);
Route::post('/logout', [AuthController::class, 'doLogout']);
Route::post('/register', [AuthController::class, 'store']);
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware(['auth'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

// 認証メールの再送（メール認証誘導画面の「再送する」ボタン）
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
