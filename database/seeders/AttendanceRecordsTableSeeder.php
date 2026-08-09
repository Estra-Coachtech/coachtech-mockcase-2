<?php

namespace Database\Seeders;

use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 勤怠レコードのシーダー。
 *
 * user1（user1@example.com）に対して、マイ勤怠レポート機能の採点用に
 * 意図的な勤怠データを作成する。全レコードに固定休憩 12:00-13:00（1時間）を付与。
 *
 * ─── 採点者向け：user1 で /attendance/report を開くと表示される予測値 ───
 *
 *  過去 5 ヶ月（先月〜5 ヶ月前）: 平日 15 日 × 5 ヶ月 = 75 日（通常勤務 9:00-18:00 / 8h労働）
 *
 *  当月: 計 17 日
 *      - 通常勤務 10 日（9:00-18:00）  → 1 日 8h 労働 / 残業 0
 *      - 残業    3 日（9:00-20:00）    → 1 日 10h 労働 / 残業 2h
 *      - 遅刻    2 日（9:30-18:00）    → 1 日 7h30m 労働 / 残業 0
 *      - 早退    1 日（9:00-17:00）    → 1 日 7h 労働 / 残業 0
 *      - 長時間  1 日（8:00-21:00）    → 1 日 12h 労働 / 残業 4h
 *
 *  → 基本サマリー（過去 6 ヶ月）の予測値:
 *      - 総労働時間: 75日×8h + (10×8h + 3×10h + 2×7.5h + 1×7h + 1×12h)
 *                  = 600h + 144h = 744 時間
 *      - 総残業時間: 0 + (3×2h + 1×4h) = 10 時間
 *      - 平均労働時間 / 日: 744h ÷ 92日 ≒ 8 時間 5 分
 *
 *  → 異常検知（当月）の予測値:
 *      - 遅刻回数: 2 回
 *      - 早退回数: 1 回
 *      - 長時間労働回数: 1 日
 *
 * user2 / user3 には直近3ヶ月の平日に実運用に近い勤怠＋固定休憩（12:00-13:00）を作成する。
 */
class AttendanceRecordsTableSeeder extends Seeder
{
    /** [clock_in, clock_out, comment] 形式のパターン定義 */
    private const PATTERN_NORMAL = ['09:00:00', '18:00:00', '通常勤務'];

    private const PATTERN_OVERTIME = ['09:00:00', '20:00:00', '残業'];

    private const PATTERN_LATE = ['09:30:00', '18:00:00', '遅刻'];

    private const PATTERN_EARLY_LEAVE = ['09:00:00', '17:00:00', '早退'];

    private const PATTERN_LONG_WORK = ['08:00:00', '21:00:00', '長時間労働'];

    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        $now = Carbon::now();

        $records = [];

        // 過去 5 ヶ月：各月 平日 15 日の通常勤務
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $monthStart = $now->copy()->subMonths($monthOffset)->startOfMonth();
            $records = array_merge($records, $this->buildMonth(
                $user1->id,
                $monthStart,
                15,
                [self::PATTERN_NORMAL],
            ));
        }

        // 当月：通常 10 + 残業 3 + 遅刻 2 + 早退 1 + 長時間 1 = 17 日
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthPatterns = array_merge(
            array_fill(0, 10, self::PATTERN_NORMAL),
            array_fill(0, 3, self::PATTERN_OVERTIME),
            array_fill(0, 2, self::PATTERN_LATE),
            array_fill(0, 1, self::PATTERN_EARLY_LEAVE),
            array_fill(0, 1, self::PATTERN_LONG_WORK),
        );
        $records = array_merge($records, $this->buildMonth(
            $user1->id,
            $thisMonthStart,
            count($thisMonthPatterns),
            $thisMonthPatterns,
        ));

        // user1 の勤怠と固定休憩を投入
        foreach ($records as $r) {
            $id = DB::table('attendance_records')->insertGetId($r);

            AttendanceBreak::create([
                'attendance_record_id' => $id,
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
            ]);
        }

        // user2 / user3 にも実運用に近い勤怠＋休憩データを作成する（直近3ヶ月の平日）
        $otherUsers = User::whereIn('email', ['user2@example.com', 'user3@example.com'])->get();
        foreach ($otherUsers as $otherUser) {
            $this->buildRecentWeekdays($otherUser->id);
        }
    }

    /**
     * 指定ユーザーに直近3ヶ月の平日分の勤怠＋固定休憩（12:00-13:00）を作成する。
     *
     * 件数・日付分布を実運用に近づけるため、平日のみ・概ね定時勤務とし、
     * 5の倍数日のみ残業（19:00 退勤）とする。
     */
    private function buildRecentWeekdays(int $userId): void
    {
        $start = Carbon::now()->subMonths(3)->startOfMonth();
        $end = Carbon::now();

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (! $day->isWeekday()) {
                continue;
            }

            // 5の倍数日は残業（19:00 退勤）、それ以外は定時（18:00 退勤）
            $clockOutHour = ($day->day % 5 === 0) ? 19 : 18;
            $breakMinutes = 60; // 12:00-13:00
            $workedMinutes = ($clockOutHour - 9) * 60 - $breakMinutes;

            $attendanceId = DB::table('attendance_records')->insertGetId([
                'user_id' => $userId,
                'date' => $day->format('Y-m-d'),
                'clock_in' => '09:00:00',
                'clock_out' => sprintf('%02d:00:00', $clockOutHour),
                'total_break_time' => '01:00',
                'total_time' => sprintf('%02d:%02d', intdiv($workedMinutes, 60), $workedMinutes % 60),
                'comment' => $clockOutHour === 19 ? '残業' : '通常勤務',
                'created_at' => $day->copy(),
                'updated_at' => $day->copy(),
            ]);

            DB::table('breaks')->insert([
                'attendance_record_id' => $attendanceId,
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
                'created_at' => $day->copy(),
                'updated_at' => $day->copy(),
            ]);
        }
    }

    /**
     * 指定月の平日に勤怠レコードを並べる。
     *
     * @param  array<int, array{0:string,1:string,2:string}>  $patterns
     * @return array<int, array<string, mixed>>
     */
    private function buildMonth(int $userId, Carbon $monthStart, int $count, array $patterns): array
    {
        $records = [];
        $patternIndex = 0;
        $cursor = $monthStart->copy();
        $patternsCount = count($patterns);

        while (count($records) < $count && $cursor->month === $monthStart->month) {
            if ($cursor->isWeekday()) {
                [$clockIn, $clockOut, $comment] = $patterns[$patternIndex % $patternsCount];

                // 全レコードに固定休憩 12:00-13:00（60分）を付与するため、合計時間も同前提で算出する
                $inMin = (int) substr($clockIn, 0, 2) * 60 + (int) substr($clockIn, 3, 2);
                $outMin = (int) substr($clockOut, 0, 2) * 60 + (int) substr($clockOut, 3, 2);
                $workedMin = ($outMin - $inMin) - 60;

                $records[] = [
                    'user_id' => $userId,
                    'date' => $cursor->format('Y-m-d'),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_break_time' => '01:00',
                    'total_time' => sprintf('%02d:%02d', intdiv($workedMin, 60), $workedMin % 60),
                    'comment' => $comment,
                    'created_at' => $cursor->copy(),
                    'updated_at' => $cursor->copy(),
                ];
                $patternIndex++;
            }
            $cursor->addDay();
        }

        return $records;
    }
}
