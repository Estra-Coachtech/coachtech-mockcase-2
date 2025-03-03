<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;
use App\Models\Application;

class UserAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function it_displays_error_message_when_clock_in_time_is_after_clock_out_time()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '10:00',
            'new_clock_out' => '9:00',
            'comment' => 'Test comment'
        ]);

        $response->assertSessionHasErrors(['new_clock_out']);
        $this->assertContains('出勤時間もしくは退勤時間が不適切な値です。', session('errors')->get('new_clock_out'));
    }

    /** @test */
    public function it_displays_error_message_when_break_start_time_is_after_clock_out_time()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

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
    public function it_displays_error_message_when_break_end_time_is_after_clock_out_time()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

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
    public function it_displays_error_message_when_remarks_are_empty()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '9:00',
            'new_clock_out' => '15:00',
            'comment' => ''
        ]);

        $response->assertSessionHasErrors(['comment']);
        $this->assertContains('備考を記入してください。', session('errors')->get('comment'));
    }

    /** @test */
    public function it_executes_modification_request_process()
    {
        $user = User::all()->random();
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $application = Application::create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'application_date' => now(),
            'new_date' => now(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '15:00',
            'comment' => 'テストコメント' ,
            'approval_status' => '承認待ち',
    ]);

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '9:00',
            'new_clock_out' => '15:00',
            'comment' => 'テストコメント',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/stamp_correction_request/approve/' . $application->id);
        $response->assertStatus(200);
        $response->assertSee($user->name);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee($application->date);
    }

    /** @test */
    public function it_displays_all_requests_in_pending_status_for_logged_in_user()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '9:00',
            'new_clock_out' => '15:00',
            'comment' => 'テストコメント',
        ]);

        $response->assertStatus(302);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $records = Application::whereHas('attendanceRecord', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('approval_status', '承認待ち')->get();
    }

    /** @test */
    public function it_displays_all_approved_requests_by_admin()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $application = Application::create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'application_date' => now(),
            'new_date' => now(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '15:00',
            'comment' => 'テストコメント',
            'approval_status' => '承認待ち'
        ]);

        $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '15:00',
            'comment' => 'テストコメント',
        ]);

        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $application->approval_status = '承認済み';
        $application->save();

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee($application->date);
    }

    /** @test */
    public function it_navigates_to_request_detail_page_when_detail_button_is_clicked()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->post('/attendance/' . $attendanceRecord->id, [
            'new_clock_in' => '9:00',
            'new_clock_out' => '15:00',
            'comment' => 'テストコメント',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response = $this->get('attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
    }
}