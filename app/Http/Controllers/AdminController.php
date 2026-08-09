<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminController extends Controller
{
    /**
     * 指定日の全ユーザーの勤怠一覧を表示する。
     *
     * @param  Request  $request  日付（date）を含むリクエスト
     * @return View 勤怠一覧画面のビュー
     */
    public function list(Request $request): View
    {
        $users = User::all();
        $date = Carbon::parse($request->query('date', Carbon::now()));
        // N+1 を避けるため user を Eager Load する
        $attendanceRecords = AttendanceRecord::with('user')->whereDate('date', $date)->whereIn('user_id', $users->pluck('id'))->get();

        return view('admin/admin-attendance-list', [
            'users' => $users,
            'attendanceRecords' => $attendanceRecords,
            'date' => $date,
            'previousDay' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDay' => $date->copy()->addDay()->format('Y-m-d'),
        ]);
    }

    /**
     * スタッフ（一般ユーザー）一覧を表示する。
     *
     * @return View スタッフ一覧画面のビュー
     */
    public function staffList(): View
    {
        // スタッフ一覧には一般ユーザーのみを表示する（管理者は除外）
        $users = User::where('admin_status', false)->get();

        return view('admin/staff-list', compact('users'));
    }

    /**
     * 指定スタッフの月次勤怠一覧を表示する（当該月の全日付・勤怠がない日は空欄）。
     *
     * @param  Request  $request  対象月（date）を含むリクエスト
     * @param  int  $id  対象スタッフのユーザーID
     * @return View スタッフ別月次勤怠一覧画面のビュー
     */
    public function staffDetailList(Request $request, int $id): View
    {
        $user = User::findOrFail($id);
        $date = Carbon::parse($request->query('date', Carbon::now()));

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // N+1 を避けるため breaks を Eager Load し、日付をキーに引けるようにする
        $records = AttendanceRecord::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn ($rec) => Carbon::parse($rec->date)->format('Y-m-d'));

        // 当該月の1ヶ月分すべての日付を行として並べる（勤怠がない日は空欄）
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $formattedAttendanceRecords = collect();
        for ($day = $startOfMonth->copy(); $day->lte($endOfMonth); $day->addDay()) {
            $record = $records->get($day->format('Y-m-d'));
            $formattedAttendanceRecords->push([
                'id' => $record?->id,
                'date' => $day->format('m/d')."({$weekdays[$day->dayOfWeek]})",
                'clock_in' => $record?->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : null,
                'clock_out' => $record?->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : null,
                'total_time' => $record?->total_time,
                'total_break_time' => $record?->total_break_time,
            ]);
        }

        return view(
            'admin/staff-attendance-list',
            [
                'user' => $user,
                'date' => $date,
                'formattedAttendanceRecords' => $formattedAttendanceRecords,
                'previousMonth' => $date->copy()->subMonth()->format('Y-m'),
                'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
            ]
        );
    }

    /**
     * 管理者向けの勤怠詳細画面を表示する。
     *
     * @param  int  $id  対象の勤怠レコードID
     * @return View 勤怠詳細画面のビュー
     */
    public function detail(int $id): View
    {
        $attendanceRecords = AttendanceRecord::with('breaks')->findOrFail($id);
        $user = User::findOrFail($attendanceRecords->user_id);

        $application = Application::where('attendance_record_id', $attendanceRecords->id)
            ->where('approval_status', '承認待ち')->get();

        // --- breaksテーブルから取得 ---
        $breaks = $attendanceRecords->breaks->map(function ($break) {
            return [
                'break_in' => $break->break_in ? Carbon::parse($break->break_in)->format('H:i') : '',
                'break_out' => $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : '',
            ];
        })->toArray();

        $attendanceRecord = [
            'application' => $attendanceRecords->application,
            'id' => $attendanceRecords->id,
            'year' => $attendanceRecords->date ? Carbon::parse($attendanceRecords->date)->format('Y年') : null,
            'date' => $attendanceRecords->date ? Carbon::parse($attendanceRecords->date)->format('n月j日') : null,
            'clock_in' => $attendanceRecords->clock_in ? Carbon::parse($attendanceRecords->clock_in)->format('H:i') : null,
            'clock_out' => $attendanceRecords->clock_out ? Carbon::parse($attendanceRecords->clock_out)->format('H:i') : null,
            'breaks' => $breaks,
            'comment' => $attendanceRecords->comment,
        ];

        return view('admin.admin-detail', compact('user', 'attendanceRecord', 'application'));
    }

    /**
     * 管理者が勤怠を直接修正する。
     *
     * @param  CorrectionRequest  $request  修正後の日付・出退勤・休憩・備考を含むリクエスト
     * @param  int  $id  対象の勤怠レコードID
     * @return View 修正後の勤怠詳細画面のビュー
     */
    public function amendmentApplication(CorrectionRequest $request, int $id): View
    {
        $attendance = AttendanceRecord::findOrFail($id);
        $user = User::findOrFail($attendance->user_id);

        // --- 日付変換（配列で来る場合にも対応） ---
        $dateString = $request->new_date;
        if (is_array($dateString)) {
            $dateString = $dateString[1];
        }
        $parsedDate = Carbon::createFromFormat('n月j日', $dateString)->year(now()->year)->format('Y-m-d');
        $attendance->date = $parsedDate;

        // --- 出退勤時間の更新 ---
        $attendance->clock_in = Carbon::parse($request->new_clock_in)->format('H:i');
        $attendance->clock_out = Carbon::parse($request->new_clock_out)->format('H:i');

        // --- 備考の保存 ---
        $attendance->comment = $request->comment;
        $attendance->save();

        // --- 休憩時間の更新 ---
        $attendance->breaks()->delete(); // 既存休憩を削除

        $breakIns = $request->input('new_break_in', []);
        $breakOuts = $request->input('new_break_out', []);
        $totalBreakMinutes = 0;

        for ($i = 0; $i < count($breakIns); $i++) {
            if (! empty($breakIns[$i]) && ! empty($breakOuts[$i])) {
                $breakIn = Carbon::parse($breakIns[$i])->format('H:i');
                $breakOut = Carbon::parse($breakOuts[$i])->format('H:i');

                $attendance->breaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);

                $totalBreakMinutes += Carbon::parse($breakOut)->diffInMinutes(Carbon::parse($breakIn));
            }
        }

        // --- 勤務時間・休憩時間の計算 ---
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);
        $totalWorkedMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;

        $attendance->total_break_time = sprintf('%02d:%02d', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60);
        $attendance->total_time = sprintf('%02d:%02d', intdiv($totalWorkedMinutes, 60), $totalWorkedMinutes % 60);
        $attendance->save();

        return app(AdminController::class)->detail($id);
    }

    /**
     * 修正申請一覧（全ユーザー）を表示する。
     *
     * @return View 修正申請一覧画面のビュー
     */
    public function applicationList(): View
    {
        $user = User::all();
        $applications = Application::all();

        return view('admin/admin-application-list', compact('user', 'applications'));
    }

    /**
     * 修正申請の承認画面を表示する。
     *
     * @param  int  $id  対象の修正申請ID
     * @return View 修正申請の承認画面のビュー
     */
    public function approvalShow(int $id): View
    {
        $application = Application::findOrFail($id);
        $user = User::findOrFail($application->user_id);

        $application->new_date = Carbon::parse($application->new_date);
        $application->new_clock_in = $application->new_clock_in ? Carbon::parse($application->new_clock_in)->format('H:i') : null;
        $application->new_clock_out = $application->new_clock_out ? Carbon::parse($application->new_clock_out)->format('H:i') : null;
        $application->new_break_in = $application->new_break_in ? Carbon::parse($application->new_break_in)->format('H:i') : null;
        $application->new_break_out = $application->new_break_out ? Carbon::parse($application->new_break_out)->format('H:i') : null;

        return view('admin/admin-application-detail', compact('user', 'application'));
    }

    /**
     * 修正申請を承認し、申請内容を勤怠へ反映する。
     *
     * @param  Request  $request  リクエスト（承認処理では未使用）
     * @param  int  $id  対象の修正申請ID
     * @return View 修正申請一覧画面のビュー
     */
    public function approval(Request $request, int $id): View
    {
        $application = Application::findOrFail($id);
        $user = User::findOrFail($application->user_id);
        $attendanceRecord = AttendanceRecord::findOrFail($application->attendance_record_id);

        $application->approval_status = '承認済み';
        $application->save();

        $attendanceRecord->date = $application->new_date;
        $attendanceRecord->clock_in = $application->new_clock_in;
        $attendanceRecord->clock_out = $application->new_clock_out;
        $attendanceRecord->comment = $application->comment;

        // 既存のbreaksを削除
        $attendanceRecord->breaks()->delete();

        // application_breaks → breaksへコピー
        foreach ($application->proposalBreaks as $applicationBreak) {
            $attendanceRecord->breaks()->create([
                'break_in' => $applicationBreak->break_in,
                'break_out' => $applicationBreak->break_out,
            ]);
        }

        $clockIn = Carbon::parse($attendanceRecord->clock_in);
        $clockOut = Carbon::parse($attendanceRecord->clock_out);

        $totalBreakTime = 0;
        foreach ($attendanceRecord->breaks as $break) {
            if ($break->break_in && $break->break_out) {
                $breakIn = Carbon::parse($break->break_in);
                $breakOut = Carbon::parse($break->break_out);
                $totalBreakTime += $breakIn->diffInMinutes($breakOut);
            }
        }

        $totalBreakHours = floor($totalBreakTime / 60);
        $totalBreakMinutes = $totalBreakTime % 60;
        $attendanceRecord->total_break_time = sprintf('%02d:%02d', $totalBreakHours, $totalBreakMinutes);

        $totalWorkedMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakTime;
        $hours = floor($totalWorkedMinutes / 60);
        $minutes = $totalWorkedMinutes % 60;
        $attendanceRecord->total_time = sprintf('%02d:%02d', $hours, $minutes);

        $attendanceRecord->save();

        return app(AdminController::class)->applicationList($id);
    }

    /**
     * 指定スタッフ・月の勤怠を CSV で出力する。
     *
     * @param  Request  $request  対象ユーザーID（user_id）と対象月（year_month）を含むリクエスト
     * @return Response CSV ファイルのダウンロードレスポンス
     */
    public function export(Request $request): Response
    {
        $userId = $request->input('user_id');
        $yearMonth = $request->input('year_month');
        $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();

        $staffAttendance = AttendanceRecord::where('user_id', $userId)->whereBetween('date', [$startDate, $endDate])->get();

        $user = User::find($userId);
        $userName = $user->name;

        $csvHeader = [
            '日付',
            '出勤時間',
            '退勤時間',
            '休憩時間',
            '勤務時間',
        ];
        $temps = [];
        array_push($temps, $csvHeader);

        foreach ($staffAttendance as $staff) {
            $temp = [
                Carbon::parse($staff->date)->format('Y/m/d'),
                Carbon::parse($staff->clock_in)->format('H:i'),
                Carbon::parse($staff->clock_out)->format('H:i'),
                $staff->total_break_time,
                $staff->total_time,
            ];
            array_push($temps, $temp);
        }
        $stream = fopen('php://temp', 'r+b');
        foreach ($temps as $temp) {
            fputcsv($stream, $temp);
        }
        rewind($stream);
        $csv = str_replace(PHP_EOL, "\r\n", stream_get_contents($stream));
        $csv = mb_convert_encoding($csv, 'SJIS-win', 'UTF-8');
        $filename = "{$userName}さんの勤怠リスト.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ];

        return response($csv, 200, $headers);
    }
}
