<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * 公開API v1 — 勤怠データの取得・操作を JSON で提供する。
 *
 * 読み取り系（index, show）は認証不要。
 * 書き込み系（store, update, destroy）は Sanctum 認証必須、
 * かつ AttendanceRecordPolicy で本人のみが更新・削除できる。
 */
class AttendanceRecordController extends Controller
{
    use AuthorizesRequests;

    /**
     * 勤怠一覧を取得する。
     *
     * クエリパラメータで user_id / date / month による絞り込み、
     * page / per_page によるページネーションが可能（デフォルト 20、最大 100）。
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 20);

        $records = AttendanceRecord::query()
            ->with(['user', 'breaks'])
            ->when(
                $validated['user_id'] ?? null,
                fn ($q, $userId) => $q->where('user_id', $userId)
            )
            ->when(
                $validated['date'] ?? null,
                fn ($q, $date) => $q->whereDate('date', $date)
            )
            ->when(
                $validated['month'] ?? null,
                fn ($q, $month) => $q
                    ->whereYear('date', substr($month, 0, 4))
                    ->whereMonth('date', substr($month, 5, 2))
            )
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($records);
    }

    /**
     * 指定 ID の勤怠詳細を取得する。
     *
     * 関連する user / breaks / applications を含めて返却する。
     * 存在しない ID の場合は Exception Handler により 404 JSON が返る。
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'breaks', 'applications']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を新規登録する。
     *
     * 認証ユーザー（Sanctum）の user_id が自動付与される。
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $attendanceRecord = $request->user()
            ->attendanceRecords()
            ->create($request->validated());

        $attendanceRecord->load(['user', 'breaks']);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 指定 ID の勤怠を更新する。
     *
     * AttendanceRecordPolicy@update により、本人のみが更新できる。
     */
    public function update(
        UpdateAttendanceRecordRequest $request,
        AttendanceRecord $attendanceRecord
    ): AttendanceRecordResource {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->validated());
        $attendanceRecord->load(['user', 'breaks']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定 ID の勤怠を削除する。
     *
     * AttendanceRecordPolicy@delete により、本人のみが削除できる。
     * 関連 breaks / applications は ON DELETE CASCADE で削除される。
     */
    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
