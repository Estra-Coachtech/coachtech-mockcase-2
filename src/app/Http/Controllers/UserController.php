<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Application;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * 勤怠打刻画面を表示する。
     */
    public function index(): View
    {
        $user = Auth::user();

        if ($user->attendance_status === '退勤済') {
            $attendance = AttendanceRecord::where('user_id', $user->id)
                ->whereDate('date', now()->format('Y-m-d'))
                ->first();

            if (! $attendance) {
                $user->attendance_status = '勤務外';
                $user->save();
            }
        }

        $now = new \DateTime();
        $week = [
            0 => '日', 1 => '月', 2 => '火', 3 => '水',
            4 => '木', 5 => '金', 6 => '土',
        ];
        $weekday = $week[$now->format('w')];
        $formattedDate = $now->format("Y年n月j日({$weekday})");
        $formattedTime = $now->format('H:i');

        return view(
            'user/attendance-register',
            compact('formattedDate', 'formattedTime', 'user')
        );
    }

    /**
     * 出勤・退勤・休憩入・休憩戻の打刻を処理する。
     */
    public function attendance(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $action = $request->input('action');

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if (in_array($action, ['break_in','break_out','clock_out']) && ! $attendance) {
            return redirect('/attendance')->withErrors('出勤していません。先に「出勤」をしてください。');
        }

        if ($action === 'clock_in' && $user->attendance_status === '勤務外') {
            $attendance             = new AttendanceRecord();
            $attendance->user_id    = $user->id;
            $attendance->date       = now();
            $attendance->clock_in   = Carbon::now();
            $attendance->save();

            $user->attendance_status = '出勤中';
            $user->save();

        } elseif ($action === 'break_in' && $user->attendance_status === '出勤中') {
            $attendance->breaks()->create([
                'break_in' => Carbon::now(),
            ]);

            $user->attendance_status = '休憩中';
            $user->save();

        } elseif ($action === 'break_out' && $user->attendance_status === '休憩中') {
            $currentBreak = $attendance
                ->breaks()
                ->whereNull('break_out')
                ->latest()
                ->first();

            if ($currentBreak) {
                $currentBreak->break_out = Carbon::now();
                $currentBreak->save();
            }

            $user->attendance_status = '出勤中';
            $user->save();

        } elseif ($action === 'clock_out' && $user->attendance_status === '出勤中') {
            $attendance->clock_out = Carbon::now();

            $clockIn  = Carbon::parse($attendance->clock_in);
            $clockOut = Carbon::parse($attendance->clock_out);

            $totalBreakTime = 0;
            foreach ($attendance->breaks as $break) {
                if ($break->break_in && $break->break_out) {
                    $totalBreakTime += Carbon::parse($break->break_in)
                        ->diffInMinutes(Carbon::parse($break->break_out));
                }
            }

            $attendance->total_break_time = sprintf(
                '%02d:%02d',
                floor($totalBreakTime/60),
                $totalBreakTime%60
            );

            $workedMins = $clockIn->diffInMinutes($clockOut) - $totalBreakTime;
            $attendance->total_time = sprintf(
                '%02d:%02d',
                floor($workedMins/60),
                $workedMins%60
            );

            $attendance->save();

            $user->attendance_status = '退勤済';
            $user->save();
        }

        return redirect('/attendance');
    }

    /**
     * ログインユーザーの月次勤怠一覧を表示する（当該月の全日付・勤怠がない日は空欄）。
     */
    public function list(Request $request): View
    {
        $user = Auth::user();
        $date = Carbon::parse($request->query('date', now()));

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth   = $date->copy()->endOfMonth();

        // N+1 を避けるため breaks を Eager Load し、日付をキーに引けるようにする
        $records = AttendanceRecord::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn ($rec) => Carbon::parse($rec->date)->format('Y-m-d'));

        // 当該月の1ヶ月分すべての日付を行として並べる（勤怠がない日は空欄）
        $weekdays = ['日','月','火','水','木','金','土'];
        $formatted = collect();
        for ($day = $startOfMonth->copy(); $day->lte($endOfMonth); $day->addDay()) {
            $record = $records->get($day->format('Y-m-d'));
            $formatted->push([
                'id'               => $record?->id,
                'date'             => $day->format('m/d') . "({$weekdays[$day->dayOfWeek]})",
                'clock_in'         => $record?->clock_in  ? Carbon::parse($record->clock_in)->format('H:i') : null,
                'clock_out'        => $record?->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : null,
                'total_time'       => $record?->total_time,
                'total_break_time' => $record?->total_break_time,
            ]);
        }

        return view('user/user-attendance-list', [
            'formattedAttendanceRecords' => $formatted,
            'date'          => $date,
            'nextMonth'     => $date->copy()->addMonth()->format('Y-m'),
            'previousMonth' => $date->copy()->subMonth()->format('Y-m'),
        ]);
    }

    /**
     * 勤怠詳細画面を表示する。承認待ち申請がある場合はその内容を併せて渡す。
     */
    public function detail(int $id): View
    {
        $attendanceRecord = AttendanceRecord::findOrFail($id);
        $user = Auth::user();

        // マスター休憩
        $masterBreaks = $attendanceRecord->breaks()->get()->map(function ($break) {
            return [
                'break_in'  => $break->break_in ? Carbon::parse($break->break_in)->format('H:i') : null,
                'break_out' => $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : null,
            ];
        })->toArray();

        // 未承認申請
        $application = Application::where('attendance_record_id', $id)
            ->where('approval_status', '承認待ち')
            ->first();
        $proposal = [];
        if ($application) {
            $proposal = $application->proposalBreaks()->get()->map(function ($break) {
                return [
                    'break_in'  => Carbon::parse($break->break_in)->format('H:i'),
                    'break_out' => $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : null,
                ];
            })->toArray();
        }

        $data = [
            'id'            => $attendanceRecord->id,
            'year'          => $attendanceRecord->date
                                ? Carbon::parse($attendanceRecord->date)->format('Y年')
                                : null,
            'date'          => $attendanceRecord->date
                                ? Carbon::parse($attendanceRecord->date)->format('n月j日')
                                : null,
            'clock_in'      => $attendanceRecord->clock_in
                                ? Carbon::parse($attendanceRecord->clock_in)->format('H:i')
                                : null,
            'clock_out'     => $attendanceRecord->clock_out
                                ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
                                : null,
            'breaks'        => $masterBreaks,
            'proposal_breaks'=> $proposal,
            'comment'       => $attendanceRecord->comment,
            'application'   => $application,
        ];

        return view('user/user-detail', compact('user', 'data'));
    }

    /**
     * 勤怠の修正申請を作成する。
     */
    public function amendmentApplication(CorrectionRequest $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $application = Application::create([
            'user_id'              => $user->id,
            'attendance_record_id' => $id,
            'approval_status'      => '承認待ち',
            'application_date'     => now(),
            'new_date'             => Carbon::createFromFormat('n月j日', $request->new_date)
                                        ->year(now()->year)
                                        ->format('Y-m-d'),
            'new_clock_in'         => Carbon::parse($request->new_clock_in)->format('H:i'),
            'new_clock_out'        => Carbon::parse($request->new_clock_out)->format('H:i'),
            'comment'              => $request->comment,
        ]);

        // 申請用休憩を作成
        $rawIns  = (array) $request->input('new_break_in', []);
        $rawOuts = (array) $request->input('new_break_out', []);
        $pairs = [];
        foreach (array_values(array_filter($rawIns)) as $i => $in) {
            $out = array_values(array_filter($rawOuts))[$i] ?? null;
            $pairs[] = [
                'break_in'  => Carbon::parse($in)->format('H:i'),
                'break_out' => $out ? Carbon::parse($out)->format('H:i') : null,
            ];
        }
        $application->proposalBreaks()->createMany($pairs);

        return redirect('/stamp_correction_request/list');
    }

    /**
     * ログインユーザーの修正申請一覧を表示する。
     */
    public function applicationList(): View
    {
        $user         = Auth::user();
        $applications = Application::where('user_id', $user->id)->get();

        // 一覧表示のために必要なデータを取得
        $formattedApplications = $applications->map(function ($application) {
            return [
                'id'            => $application->id,
                'application_date' => $application->application_date
                                ? Carbon::parse($application->application_date)->format('Y/m/d')
                                : null,
                'date'          => $application->new_date
                                ? Carbon::parse($application->new_date)->format('Y/m/d')
                                : null,
                'clock_in'      => $application->new_clock_in,
                'clock_out'     => $application->new_clock_out,
                'comment'       => $application->comment,
                'approval_status' => $application->approval_status,
            ];
        });

        return view(
            'user/user-application-list',
            compact('user', 'formattedApplications')
        );
    }

    /**
     * 修正申請の詳細画面を表示する。
     */
    public function applicationDetail(int $id): View
    {
        $user = Auth::user();
        $application = Application::findOrFail($id);

        $proposalBreaks = $application->proposalBreaks()->get()->map(function ($break) {
            return [
                'break_in'  => $break->break_in ? Carbon::parse($break->break_in)->format('H:i') : null,
                'break_out' => $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : null,
            ];
        })->toArray();

        $data = [
            'id'            => $application->id,
            'year'          => $application->new_date
                                ? Carbon::parse($application->new_date)->format('Y年')
                                : null,
            'date'          => $application->new_date
                                ? Carbon::parse($application->new_date)->format('n月j日')
                                : null,
            'clock_in'      => $application->new_clock_in
                                ? Carbon::parse($application->new_clock_in)->format('H:i')
                                : null,
            'clock_out'     => $application->new_clock_out
                                ? Carbon::parse($application->new_clock_out)->format('H:i')
                                : null,
            'breaks'        => $proposalBreaks,
            'comment'       => $application->comment,
            'application'   => $application,
        ];

        return view('user/user-detail', compact('user', 'data'));
    }
}
