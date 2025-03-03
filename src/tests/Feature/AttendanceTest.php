<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Database\Seeders\UsersTableSeeder;
use App\Models\User;
use App\Models\AttendanceRecord;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UsersTableSeeder::class);
    }

    public function attendance_button_functionality()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('出勤');

        $response = $this->post('/attendance', ['action' => 'clock_in']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'attendance_status' => '勤務中'
        ]);
    }

    /** @test */
    public function cannot_clock_in_twice_in_a_day()
    {
        $user = User::factory()->create(['attendance_status' => '勤務外']);
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('勤務外');

        $response = $this->post('/attendance', ['action' => 'clock_in']);

        $response->assertRedirect('/attendance');

        $response = $this->post('/attendance', ['action' => 'clock_out']);

        $response->assertRedirect('/attendance');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'attendance_status' => '退勤済',
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    /** @test */
    public function attendance_time_recorded_in_management_system()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $this->post('/attendance', ['action' => 'clock_in']);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();
        $this->assertNotNull($attendanceRecord);
        $this->assertEquals(now()->format('Y-m-d'), $attendanceRecord->date->format('Y-m-d'));
    }
}