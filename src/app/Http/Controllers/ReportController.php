<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * マイ勤怠レポート機能。
 *
 * ログインユーザーの直近 6 ヶ月の勤怠データから、以下の統計を計算する。
 *  - 基本サマリー（総労働時間 / 総残業時間 / 平均労働時間）
 *  - 月次推移（過去 6 ヶ月の労働時間・残業時間）
 *  - 異常検知（直近月の遅刻 / 早退 / 長時間労働 回数）
 *
 * 計算は Collection メソッド（map / filter / groupBy / sum / avg）を徹底活用する。
 */
class ReportController extends Controller
{
    /** 始業時刻（遅刻判定の閾値） */
    private const WORK_START = '09:00';

    /** 終業時刻（早退判定の閾値） */
    private const WORK_END = '18:00';

    /** 1日の標準労働時間（分）。これを超えると残業として加算 */
    private const STANDARD_WORK_MINUTES = 480;

    /** 長時間労働の閾値（分）。これを超えた日は「長時間労働」として計上 */
    private const LONG_WORK_THRESHOLD_MINUTES = 600;

    /**
     * マイ勤怠レポートを表示する。
     */
    public function index(): View
    {
        $userId = Auth::id();
        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();

        $records = AttendanceRecord::query()
            ->with('breaks')
            ->where('user_id', $userId)
            ->where('date', '>=', $sixMonthsAgo)
            ->whereNotNull('clock_out')
            ->get();

        $stats = $this->buildStats($records);

        return view('reports.index', [
            'summary' => $this->buildSummary($stats),
            'monthlyTrend' => $this->buildMonthlyTrend($stats),
            'anomalies' => $this->buildAnomalies($stats),
        ]);
    }

    /**
     * 値を "H:i:s" 形式の時刻文字列に正規化する。
     *
     * AttendanceRecord の cast `datetime:H:i` により clock_in/clock_out は
     * Carbon インスタンスで返るため、文字列連結する前に時刻部分のみ取り出す。
     */
    private function toTimeString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        return (string) $value;
    }

    /**
     * 各勤怠レコードから労働時間・残業時間の中間集計を作る。
     *
     * @param  Collection<int, AttendanceRecord>  $records
     * @return Collection<int, array<string, mixed>>
     */
    private function buildStats(Collection $records): Collection
    {
        return $records
            ->map(function (AttendanceRecord $r): array {
                $dateStr = $r->date instanceof \DateTimeInterface
                    ? $r->date->format('Y-m-d')
                    : (string) $r->date;

                $clockInStr = $this->toTimeString($r->clock_in);
                $clockOutStr = $this->toTimeString($r->clock_out);

                $clockIn = Carbon::parse($dateStr.' '.$clockInStr);
                $clockOut = Carbon::parse($dateStr.' '.$clockOutStr);
                $grossMinutes = $clockOut->diffInMinutes($clockIn);

                $breakMinutes = $r->breaks
                    ->filter(fn ($b) => $b->break_out !== null)
                    ->reduce(function (int $carry, $b) use ($dateStr): int {
                        $bIn = Carbon::parse($dateStr.' '.$this->toTimeString($b->break_in));
                        $bOut = Carbon::parse($dateStr.' '.$this->toTimeString($b->break_out));

                        return $carry + $bOut->diffInMinutes($bIn);
                    }, 0);

                $workMinutes = max(0, $grossMinutes - $breakMinutes);
                $overtimeMinutes = max(0, $workMinutes - self::STANDARD_WORK_MINUTES);

                return [
                    'date' => Carbon::parse($dateStr),
                    'clock_in' => substr($clockInStr, 0, 5),
                    'clock_out' => substr($clockOutStr, 0, 5),
                    'work_minutes' => $workMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                ];
            });
    }

    /**
     * 基本サマリーを構築する。
     *
     * @param  Collection<int, array<string, mixed>>  $stats
     * @return array<string, int>
     */
    private function buildSummary(Collection $stats): array
    {
        $count = $stats->count();

        return [
            'total_work_minutes' => (int) $stats->sum('work_minutes'),
            'total_overtime_minutes' => (int) $stats->sum('overtime_minutes'),
            'avg_work_minutes' => $count === 0 ? 0 : (int) round($stats->avg('work_minutes')),
        ];
    }

    /**
     * 月次推移（過去 6 ヶ月）を構築する。
     *
     * @param  Collection<int, array<string, mixed>>  $stats
     * @return Collection<int, array<string, mixed>>
     */
    private function buildMonthlyTrend(Collection $stats): Collection
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => Carbon::now()->subMonths($i)->format('Y-m'))
            ->map(function (string $month) use ($stats): array {
                $monthStats = $stats->filter(
                    fn (array $s): bool => $s['date']->format('Y-m') === $month
                );

                return [
                    'month' => $month,
                    'work_minutes' => (int) $monthStats->sum('work_minutes'),
                    'overtime_minutes' => (int) $monthStats->sum('overtime_minutes'),
                ];
            });
    }

    /**
     * 異常検知（直近月の遅刻 / 早退 / 長時間労働の回数）を構築する。
     *
     * @param  Collection<int, array<string, mixed>>  $stats
     * @return array<string, int>
     */
    private function buildAnomalies(Collection $stats): array
    {
        $thisMonth = Carbon::now()->format('Y-m');

        $thisMonthStats = $stats->filter(
            fn (array $s): bool => $s['date']->format('Y-m') === $thisMonth
        );

        return [
            'late_count' => $thisMonthStats
                ->filter(fn (array $s): bool => $s['clock_in'] > self::WORK_START)
                ->count(),
            'early_leave_count' => $thisMonthStats
                ->filter(fn (array $s): bool => $s['clock_out'] !== '' && $s['clock_out'] < self::WORK_END)
                ->count(),
            'long_work_count' => $thisMonthStats
                ->filter(fn (array $s): bool => $s['work_minutes'] > self::LONG_WORK_THRESHOLD_MINUTES)
                ->count(),
        ];
    }
}
