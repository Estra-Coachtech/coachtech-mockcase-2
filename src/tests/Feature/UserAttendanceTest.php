<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\DatabaseSeeder;
use Carbon\Carbon;

class UserAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function it_should_display_all_attendance_information_for_logged_in_user()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $formattedAttendanceRecords = $response->viewData('formattedAttendanceRecords');

        $this->assertNotEmpty($formattedAttendanceRecords);

    }

    /** @test */
    public function it_should_display_current_month_on_attendance_list_page()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertSee(now()->format('Y-m'));
    }

    /** @test */
    public function it_should_display_previous_month_attendance_information_when_previous_month_button_is_clicked()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $previousMonth = now()->subMonth();

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee($previousMonth->format('Y-m'));
    }

    /** @test */
    public function it_should_display_next_month_attendance_information_when_next_month_button_is_clicked()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $nextMonth = now()->addMonth();

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee($nextMonth->format('Y-m'));
    }

    /** @test */
    public function it_should_navigate_to_attendance_detail_page_when_detail_button_is_clicked()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();

        $response = $this->get('/attendance');

        $response = $this->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee($attendanceRecord->date->format('m月d日'));
    }
}