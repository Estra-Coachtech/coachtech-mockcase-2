<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'admin_status' => false,
            'attendance_status' => '勤務外',
        ]);
    }

    /** @test */
    public function guest_is_redirected_to_login(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_user_can_view_report(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk()
            ->assertSee('マイ勤怠レポート')
            ->assertSee('基本サマリー')
            ->assertSee('月次推移')
            ->assertSee('異常検知');
    }

    /** @test */
    public function user_with_no_records_sees_zero_safely(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();

        $summary = $response->viewData('summary');
        $this->assertSame(0, $summary['total_work_minutes']);
        $this->assertSame(0, $summary['avg_work_minutes']);
        $this->assertSame(0, $summary['total_overtime_minutes']);

        $anomalies = $response->viewData('anomalies');
        $this->assertSame(0, $anomalies['late_count']);
        $this->assertSame(0, $anomalies['early_leave_count']);
        $this->assertSame(0, $anomalies['long_work_count']);
    }

    /** @test */
    public function eight_hour_shift_yields_480_work_minutes_zero_overtime(): void
    {
        $user = $this->createUser();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'comment' => '',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $summary = $response->viewData('summary');
        $this->assertSame(480, $summary['total_work_minutes']);
        $this->assertSame(0, $summary['total_overtime_minutes']);
    }

    /** @test */
    public function eleven_hour_shift_yields_180_overtime_minutes(): void
    {
        $user = $this->createUser();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '20:00:00',
            'comment' => '',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $summary = $response->viewData('summary');
        // 11時間 = 660分、残業 = 660 - 480 = 180
        $this->assertSame(660, $summary['total_work_minutes']);
        $this->assertSame(180, $summary['total_overtime_minutes']);
    }

    /** @test */
    public function clock_in_after_nine_counts_as_late(): void
    {
        $user = $this->createUser();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '09:30:00',
            'clock_out' => '18:00:00',
            'comment' => '',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $anomalies = $response->viewData('anomalies');
        $this->assertSame(1, $anomalies['late_count']);
        $this->assertSame(0, $anomalies['early_leave_count']);
    }

    /** @test */
    public function clock_out_before_six_counts_as_early_leave(): void
    {
        $user = $this->createUser();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '17:30:00',
            'comment' => '',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $anomalies = $response->viewData('anomalies');
        $this->assertSame(0, $anomalies['late_count']);
        $this->assertSame(1, $anomalies['early_leave_count']);
    }

    /** @test */
    public function over_ten_hours_counts_as_long_work(): void
    {
        $user = $this->createUser();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '08:00:00',
            'clock_out' => '20:00:00',
            'comment' => '',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $anomalies = $response->viewData('anomalies');
        // 12時間勤務 = 720分 > 600分の長時間労働閾値
        $this->assertSame(1, $anomalies['long_work_count']);
    }
}
