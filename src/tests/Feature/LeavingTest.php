<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\UsersTableSeeder;

class LeavingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UsersTableSeeder::class);
    }

    /** @test */
    public function it_should_correctly_function_when_clock_out_button_is_clicked()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response = $this->post('/attendance', ['action' => 'clock_in']);

        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        $response = $this->post('/attendance', ['action' => 'clock_out']);

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    /** @test */
    public function it_should_record_clock_out_time_in_management_system()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $this->post('/attendance', ['action' => 'clock_in']);
        $this->post('/attendance', ['action' => 'clock_out']);

        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)->first();
        $this->assertNotNull($attendanceRecord);
        $this->assertEquals(now()->format('Y-m-d'), $attendanceRecord->date->format('Y-m-d'));

        $this->assertNotNull($attendanceRecord->clock_out);
    }
}
