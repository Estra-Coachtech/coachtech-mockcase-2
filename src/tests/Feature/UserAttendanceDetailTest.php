<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function it_displays_logged_in_user_name_on_attendance_detail_page()
    {
        // 勤怠記録を持つユーザーを必ず選ぶ（記録のないユーザーが選ばれると null pointer になるため）
        $attendanceRecord = AttendanceRecord::with('user', 'breaks')->whereHas('breaks')->first();
        $user = $attendanceRecord->user;
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /** @test */
    public function it_displays_selected_date_on_attendance_detail_page()
    {
        // 勤怠記録を持つユーザーを必ず選ぶ（記録のないユーザーが選ばれると null pointer になるため）
        $attendanceRecord = AttendanceRecord::with('user', 'breaks')->whereHas('breaks')->first();
        $user = $attendanceRecord->user;
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee($attendanceRecord->date->format('m月d日'));
    }

    /** @test */
    public function it_displays_correct_break_time_for_logged_in_user()
    {
        // 勤怠記録を持つユーザーを必ず選ぶ（記録のないユーザーが選ばれると null pointer になるため）
        $attendanceRecord = AttendanceRecord::with('user', 'breaks')->whereHas('breaks')->first();
        $user = $attendanceRecord->user;
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee(Carbon::parse($attendanceRecord->clock_in)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceRecord->clock_out)->format('H:i'));
    }

    /** @test */
    public function it_should_navigate_to_attendance_detail_page_when_detail_button_is_cked()
    {
        // 勤怠記録を持つユーザーを必ず選ぶ（記録のないユーザーが選ばれると null pointer になるため）
        $attendanceRecord = AttendanceRecord::with('user', 'breaks')->whereHas('breaks')->first();
        $user = $attendanceRecord->user;
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee(Carbon::parse($attendanceRecord->breaks[0]->break_in)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceRecord->breaks[0]->break_out)->format('H:i'));
    }
}
