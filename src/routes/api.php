<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 公開API v1 — 勤怠データの取得・操作を JSON で提供する。
| 読み取り系（GET）は認証不要、書き込み系（POST/PUT/DELETE）は Sanctum 認証必須。
|
*/

Route::prefix('v1')->group(function (): void {
    // 読み取り系：認証不要
    Route::get('attendance-records', [AttendanceRecordController::class, 'index']);
    Route::get('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show']);

    // 書き込み系：Sanctum 認証必須（★ 応用）
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('attendance-records', [AttendanceRecordController::class, 'store']);
        Route::put('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
        Route::patch('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
        Route::delete('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'destroy']);
    });
});
