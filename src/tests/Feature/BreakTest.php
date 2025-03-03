<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\UsersTableSeeder;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UsersTableSeeder::class);
    }

    public function it_should_correctly_function_when_break_button_is_clicked()
    {
        $user = User::factory()->create(['attendance_status' => '勤務中']);
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $response = $this->post('/attendance', ['action' => 'break_in']);

    }

    /** @test */
    public function it_should_allow_multiple_breaks_in_a_single_day()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'clock_in']);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'break_in']);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'break_out']);

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');
    }

    /** @test */
    public function it_should_correctly_function_when_break_return_button_is_clicked()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'clock_in']);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'break_in']);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'break_out']);

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function it_should_allow_multiple_break_returns_in_a_single_day()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'clock_in']);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'break_in']);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'break_out']);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'break_in']);

        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /** @test */
    public function break_time_recorded_in_management_system()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $this->post('/attendance', ['action' => 'clock_in']);
        $this->post('/attendance', ['action' => 'break_in']);
        $this->post('/attendance', ['action' => 'break_out']);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();
        $this->assertNotNull($attendanceRecord);
        $this->assertEquals(now()->format('Y-m-d'), $attendanceRecord->date->format('Y-m-d'));

        $this->assertNotNull($attendanceRecord->break_in);
        $this->assertNotNull($attendanceRecord->break_out);
    }
}
