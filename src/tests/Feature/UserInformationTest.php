<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;

class UserInformationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function it_should_allow_admin_to_view_names_and_emails_of_all_users()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $users = User::where('admin_status', false)->get();

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /** @test */
    public function it_should_display_correct_attendance_information_for_users()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::where('admin_status', false)->first();
        $attendanceRecords = AttendanceRecord::where('user_id', $user->id);

        $response = $this->get('/admin/staff/list');
        $response = $this->get('/admin/attendance/staff/' . $user->id);

        $response->assertStatus(200);
        foreach ($attendanceRecords as $record) {
            $response->assertSee($record->date);
            $response->assertSee(Carbon::parse($record->clock_in)->format('H:i'));
            $response->assertSee(Carbon::parse($record->clock_out)->format('H:i'));
        }
    }

    /** @test */
    public function it_should_display_previous_month_attendance_when_previous_month_button_is_pressed()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $response = $this->get('/admin/attendance/list');

        $previousMonth = now()->subMonth();

        $attendanceRecords = AttendanceRecord::whereDate('date', $previousMonth)->get();

        $response = $this->get('/admin/attendance/list?date=' . $previousMonth->format('Y-m-d'));

        foreach ($attendanceRecords as $record) {
            $response->assertSee($record->user->name);
            $response->assertSee(Carbon::parse($record->clock_in)->format('H:i'));
            $response->assertSee(Carbon::parse($record->clock_out)->format('H:i'));
        }
        $response->assertStatus(200);
    }

    /** @test */
    public function it_should_display_next_month_attendance_when_next_month_button_is_pressed()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $response = $this->get('/admin/attendance/list');

        $nextMonth = now()->addMonth();

        $attendanceRecords = AttendanceRecord::whereDate('date', $nextMonth)->get();

        $response = $this->get('/admin/attendance/list?date=' . $nextMonth->format('Y-m-d'));

        foreach ($attendanceRecords as $record) {
            $response->assertSee($record->user->name);
            $response->assertSee(Carbon::parse($record->clock_in)->format('H:i'));
            $response->assertSee(Carbon::parse($record->clock_out)->format('H:i'));
        }
        $response->assertStatus(200);
    }

    /** @test */
    public function it_should_navigate_to_attendance_detail_page_when_detail_button_is_pressed()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::all()->random();

        $response = $this->get('/admin/attendance/staff/' . $user->id);

        $attendanceRecords = AttendanceRecord::all()->random();

        $response = $this->get('/attendance/' . $attendanceRecords->id);
        $response->assertStatus(200);
    }
}


