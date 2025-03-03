<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function status_displayed_correctly_for_user_out_of_work()
    {
        $user = User::factory()->create(['attendance_status' => '勤務外']);
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('勤務外');
    }

    /** @test */
    public function status_displayed_correctly_for_user_working()
    {
        $user = User::factory()->create(['attendance_status' => '勤務中']);
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('勤務中');
    }

    /** @test */
    public function status_displayed_correctly_for_user_on_break()
    {
        $user = User::factory()->create(['attendance_status' => '休憩中']);
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
    }

    /** @test */
    public function status_displayed_correctly_for_user_clocked_out()
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
}