<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Database\Seeders\DatabaseSeeder;

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
        $user = User::factory()->create(['attendance_status' => '勤務外']);
        $other = User::factory()->create(['attendance_status' => '勤務外']);
        $this->actingAs($user);

        // ログインユーザー自身の勤怠（識別しやすい時刻で2件）
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => now()->startOfMonth()->format('Y-m-d'),
            'clock_in' => '08:11',
            'clock_out' => '17:11',
        ]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => now()->startOfMonth()->addDay()->format('Y-m-d'),
            'clock_in' => '08:22',
            'clock_out' => '17:22',
        ]);
        // 他ユーザーの勤怠（自分の一覧に混ざってはいけない）
        AttendanceRecord::create([
            'user_id' => $other->id,
            'date' => now()->startOfMonth()->format('Y-m-d'),
            'clock_in' => '23:44',
            'clock_out' => '23:55',
        ]);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        // 自分の勤怠はすべて表示される
        $response->assertSee('08:11');
        $response->assertSee('08:22');
        // 他人の勤怠は表示されない
        $response->assertDontSee('23:44');
    }

    /** @test */
    public function it_should_display_current_month_on_attendance_list_page()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertSee(now()->format('Y/m'));
    }

    /** @test */
    public function it_should_display_previous_month_attendance_information_when_previous_month_button_is_clicked()
    {
        $user = User::factory()->create(['attendance_status' => '勤務外']);
        $this->actingAs($user);

        $previousMonth = now()->subMonth();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $previousMonth->copy()->startOfMonth()->format('Y-m-d'),
            'clock_in' => '08:33',
            'clock_out' => '17:33',
        ]);

        // 「前月」ボタン押下に相当：前月を指定して遷移する
        $response = $this->get('/attendance/list?date=' . $previousMonth->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee($previousMonth->format('Y/m'));
        $response->assertSee('08:33');
    }

    /** @test */
    public function it_should_display_next_month_attendance_information_when_next_month_button_is_clicked()
    {
        $user = User::factory()->create(['attendance_status' => '勤務外']);
        $this->actingAs($user);

        $nextMonth = now()->addMonth();
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->startOfMonth()->format('Y-m-d'),
            'clock_in' => '08:44',
            'clock_out' => '17:44',
        ]);

        // 「翌月」ボタン押下に相当：翌月を指定して遷移する
        $response = $this->get('/attendance/list?date=' . $nextMonth->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
        $response->assertSee('08:44');
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
