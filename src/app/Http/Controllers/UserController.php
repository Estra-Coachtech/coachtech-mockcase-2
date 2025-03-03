<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Application;
use Carbon\Carbon;
use App\Http\Requests\CorrectionRequest;

class UserController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->attendance_status === '退勤済') {
            $attendance = AttendanceRecord::where('user_id', $user->id)->whereDate('date', now()->format('Y-m-d'))->first();
            if (!$attendance) {
                $user->attendance_status = '勤務外';
                $user->save();
            }
        }

        $now = new \DateTime();
        $week = [
            0 => '日',
            1 => '月',
            2 => '火',
            3 => '水',
            4 => '木',
            5 => '金',
            6 => '土',
        ];
        $weekdayIndex = $now->format('w');
        $weekday = $week[$weekdayIndex];
        $formattedDate = $now->format('Y年m月d日(' . $weekday . ')');
        $formattedTime = $now->format('H:i');
        return view('user/attendance-register', compact('formattedDate', 'formattedTime', 'user'));
    }

    public function attendance(Request $request)
    {
        $user = Auth::user();
        $action = $request->input('action');

        if ($user->attendance_status !== '勤務外') {
            $attendance = AttendanceRecord::where('user_id', $user->id)->whereDate('date', now()->format('Y-m-d'))->first();
        }

        if ($user->attendance_status === '退勤済')
        {
            $attendance = AttendanceRecord::where('user_id', $user->id)->whereDate('date', now()->format('Y-m-d'))->first();
            if (!$attendance) {
                $user->attendance_status = '勤務外';
                $user->save();
            }
        }

        if ($action === 'clock_in' && $user->attendance_status === '勤務外') {
            $attendance = new AttendanceRecord();
            $attendance->user_id = $user->id;
            $attendance->date = now();
            $attendance->clock_in = Carbon::now()->format('H:i');
            $attendance->save();

            $user->attendance_status = '出勤中';
            $user->save();
        } elseif ($action === "clock_out" && $user->attendance_status === '出勤中') {
            $attendance->clock_out = Carbon::now()->format('H:i');

            $clockIn = Carbon::parse($attendance->clock_in);
            $clockOut = Carbon::parse($attendance->clock_out);

            $totalBreakTime = 0;
            if ($attendance->break_in && $attendance->break_out) {
                $breakIn = Carbon::parse($attendance->break_in);
                $breakOut = Carbon::parse($attendance->break_out);
                $totalBreakTime += $breakIn->diffInMinutes($breakOut);
            }

            if ($attendance->break2_in && $attendance->break2_out) {
                $break2In = Carbon::parse($attendance->break2_in);
                $break2Out = Carbon::parse($attendance->break2_out);
                $totalBreakTime += $break2In->diffInMinutes($break2Out);
            }

            $totalBreakHours = floor($totalBreakTime / 60);$totalBreakMinutes = $totalBreakTime % 60;
            $attendance->total_break_time = sprintf('%02d:%02d', $totalBreakHours, $totalBreakMinutes);

            $totalWorkedMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakTime;

            $hours = floor($totalWorkedMinutes / 60);
            $minutes = $totalWorkedMinutes % 60;

            $attendance->total_time = sprintf('%02d:%02d', $hours, $minutes);

            $attendance->save();

            $user->attendance_status = '退勤済';
            $user->save();
        } elseif ($action === 'break_in' && $user->attendance_status === '出勤中') {
            if (!$attendance->break_in) {
                $attendance->break_in = Carbon::now()->format('H:i');
            } elseif (!$attendance->break2_in) {
                $attendance->break2_in = Carbon::now()->format('H:i');
            }
            $attendance->save();

            $user->attendance_status = '休憩中';
            $user->save();
        } elseif ($action === 'break_out' && $user->attendance_status === '休憩中') {
            if (!$attendance->break_out) {
                $attendance->break_out = Carbon::now()->format('H:i');
            } elseif (!$attendance->break2_out) {
                $attendance->break2_out = Carbon::now()->format('H:i');
            }
            $attendance->save();

            $user->attendance_status = '出勤中';
            $user->save();
        }
        return redirect('/attendance');
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $date = Carbon::parse($request->query('date', Carbon::now()));

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $attendanceRecords = AttendanceRecord::where('user_id', $user->id)->whereBetween('date', [$startOfMonth, $endOfMonth])->get();

        $formattedAttendanceRecords = $attendanceRecords->map(function ($attendance) {
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
            $date = Carbon::parse($attendance->date);
            $weekday = $weekdays[$date->dayOfWeek];
            return [
                'id' => $attendance->id,
                'date' => $date->format('m/d') . "($weekday)",
                'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null,
                'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null,
                'total_time' => $attendance->total_time,
                'total_break_time' => $attendance->total_break_time
            ];
        });

        return view('user/user-attendance-list',
        [
            'formattedAttendanceRecords' => $formattedAttendanceRecords,
            'date' => $date,
            'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
            'previousMonth' => $date->copy()->subMonth()->format('Y-m'),
        ]);
    }

    public function detail($id)
    {
        $attendanceRecords = AttendanceRecord::findOrFail($id);
        $user = User::findOrFail($attendanceRecords->user_id);

        $application = Application::where('attendance_record_id', $attendanceRecords->id)->where('approval_status', '承認待ち')->get();

        $applicationData = Application::where('attendance_record_id', $attendanceRecords->id)->first();

        $attendanceRecord = [
            'application' => $attendanceRecords->application,
            'id' => $attendanceRecords->id,
            'year' => $attendanceRecords->date ? Carbon::parse($attendanceRecords->date)->format('Y年') : null,
            'date' => $attendanceRecords->date ? Carbon::parse($attendanceRecords->date)->format('m月d日') : null,
            'clock_in' => $attendanceRecords->clock_in ? Carbon::parse($attendanceRecords->clock_in)->format('H:i') : null,
            'clock_out' => $attendanceRecords->clock_out ? Carbon::parse($attendanceRecords->clock_out)->format('H:i') : null,
            'break_in' => $attendanceRecords->break_in ? Carbon::parse($attendanceRecords->break_in)->format('H:i') : null,
            'break_out' => $attendanceRecords->break_out ? Carbon::parse($attendanceRecords->break_out)->format('H:i') : null,
            'break2_in' => $attendanceRecords->break2_in ? Carbon::parse($attendanceRecords->break2_in)->format('H:i') : null,
            'break2_out' => $attendanceRecords->break2_out ? Carbon::parse($attendanceRecords->break2_out)->format('H:i') : null,
            'comment' => $attendanceRecords->comment,
        ];

        return view('user/user-detail', compact('user', 'attendanceRecord','application', 'applicationData'));
    }

    public function amendmentApplication(CorrectionRequest $request, $id)
    {
        $user = Auth::user();
        $attendance = AttendanceRecord::findOrFail($id);

        $amendment = new Application();
        $amendment->user_id = $user->id;
        $amendment->attendance_record_id = $attendance->id;
        $amendment->approval_status = "承認待ち";
        $amendment->application_date = now();
        $dateString = $request->new_date;
        $parsedDate = Carbon::createFromFormat('n月j日', $dateString)->year(now()->year)->format('Y-m-d');
        $amendment->new_date = $parsedDate;
        $amendment->new_clock_in = Carbon::parse($request->new_clock_in)->format('H:i');
        $amendment->new_clock_out = Carbon::parse($request->new_clock_out)->format('H:i');
        if ($request->new_break_in) {
            $amendment->new_break_in = Carbon::parse($request->new_break_in)->format('H:i');
        }
        if ($request->new_break_out) {
            $amendment->new_break_out = Carbon::parse($request->new_break_out)->format('H:i');
        }
        if ($request->new_break2_in) {
            $amendment->new_break2_in = Carbon::parse($request->new_break2_in)->format('H:i');
        }
        if ($request->new_break2_out) {
            $amendment->new_break2_out = Carbon::parse($request->new_break2_out)->format('H:i');
        }
        $amendment->comment = $request->comment;
        $amendment->save();


        return redirect('/stamp_correction_request/list');
    }

    public function applicationList()
    {
        $user = Auth::user();
        $applications = Application::where('user_id', $user->id)->get();
        $attendance = AttendanceRecord::get('id');

        return view('user/user-application-list', compact('user', 'applications', 'attendance'));
    }
}

