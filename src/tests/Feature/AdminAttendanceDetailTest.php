<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function it_should_display_selected_attendance_data_in_attendance_detail_page()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/admin/attendance/list');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /** @test */
    public function it_should_display_error_when_clock_in_is_after_clock_out()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/admin/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '10:00',
            'new_clock_out' => '9:00',
            'comment' => 'テストコメント'
        ]);

        $response->assertSessionHasErrors(['new_clock_out']);
        $this->assertContains('出勤時間もしくは退勤時間が不適切な値です。', session('errors')->get('new_clock_out'));
    }

    /** @test */
    public function it_should_display_error_when_break_start_is_after_clock_out()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/admin/attendance');

        $response = $this->get('/admin/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '9:00',
            'new_clock_out' => '15:00',
            'new_break_in' => '16:00',
            'new_break_out' => '15:30',
            'comment' => 'Test comment'
        ]);

        $response->assertSessionHasErrors(['new_break_in']);
        $this->assertContains('休憩時間が勤務時間外です。', session('errors')->get('new_break_in'));
    }

    /** @test */
    public function it_should_display_error_when_break_end_is_after_clock_out()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/admin/attendance');

        $response = $this->get('/admin/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '9:00',
            'new_clock_out' => '15:00',
            'new_break_in' => '10:00',
            'new_break_out' => '16:00',
            'comment' => 'Test comment'
        ]);

        $response->assertSessionHasErrors(['new_break_out']);
        $this->assertContains('休憩時間が勤務時間外です。', session('errors')->get('new_break_out'));
    }

    /** @test */
    public function it_should_display_error_when_notes_are_empty()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/admin/attendance');

        $response = $this->get('/admin/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '9:00',
            'new_clock_out' => '15:00',
            'comment' => ''
        ]);

        $response->assertSessionHasErrors(['comment']);
        $this->assertContains('備考を記入してください。', session('errors')->get('comment'));
    }
}


