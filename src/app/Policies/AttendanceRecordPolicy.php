<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

/**
 * 勤怠レコードに対する認可ポリシー。
 *
 * 一般ユーザーは自分自身の勤怠のみ更新・削除可能。
 * 管理者（admin_status = true）は全ユーザーの勤怠に対して操作可能。
 */
class AttendanceRecordPolicy
{
    /**
     * すべての操作の前に評価される。管理者は常に許可する。
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->admin_status) {
            return true;
        }

        return null;
    }

    /**
     * 勤怠を更新できるか。本人のみ許可。
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 勤怠を削除できるか。本人のみ許可。
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }
}
