<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Database\Seeders\UsersTableSeeder;
use App\Models\User;

class GetDateAndTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UsersTableSeeder::class);
    }

    /** @test */
    public function current_datetime_is_displayed_correctly_on_attendance_screen()
    {
        $user = User::all()->random();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $now = new \DateTime();
        $week = [
            0 => '日',
            1 => '月',
            2 => '火',
            3 => '水',
            4 => '木',
            5 => '金',
            6 => '土',
        ];
        $weekdayIndex = $now->format('w');
        $weekday = $week[$weekdayIndex];
        $formattedDate = $now->format('Y年m月d日(' . $weekday . ')');
        $formattedTime = $now->format('H:i');

        $response->assertSee($formattedDate);
        $response->assertSee($formattedTime);
    }
}
