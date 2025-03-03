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

class AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function it_should_display_all_pending_modification_requests()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $users = User::all();
        $applications = [];

        $faker = \Faker\Factory::create();

        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $attendanceRecord = AttendanceRecord::create([
                    'user_id' => $user->id,
                    'date' => now(),
                    'clock_in' => '09:00',
                    'clock_out' => '17:00',
                ]);

                $applications[] = Application::create([
                    'approval_status' => '承認待ち',
                    'new_date' => now(),
                    'user_id' => $user->id,
                    'new_clock_in' => '10:00',
                    'new_clock_out' => '12:00',
                    'attendance_record_id' => $attendanceRecord->id,
                    'comment' => 'テストコメント',
                    'application_date' => now(),
                ]);
            }
        }

        $response = $this->get('/stamp_correction_request/list?tab=tab1');

        foreach ($applications as $application) {
            $response->assertSee($application->comment);
        }
    }

    /** @test */
    public function it_should_display_all_approved_modification_requests()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $users = User::all();
        $applications = [];

        $faker = \Faker\Factory::create();

        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $attendanceRecord = AttendanceRecord::create([
                    'user_id' => $user->id,
                    'date' => now(),
                    'clock_in' => '09:00',
                    'clock_out' => '17:00',
                ]);

                $applications[] = Application::create([
                    'approval_status' => '承認済み',
                    'new_date' => now(),
                    'user_id' => $user->id,
                    'new_clock_in' => '10:00',
                    'new_clock_out' => '12:00',
                    'attendance_record_id' => $attendanceRecord->id,
                    'comment' => 'テストコメント',
                    'application_date' => now(),
                ]);
            }
        }

        $response = $this->get('/stamp_correction_request/list?tab=tab2');

        foreach ($applications as $application) {
            $response->assertSee($application->comment);
        }

    }

    /** @test */
    public function it_should_display_correct_details_of_modification_request()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::where('admin_status', false)->first();

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => now(),
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);

        $application = Application::create([
            'approval_status' => '承認待ち',
            'user_id' => $user->id,
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => '10:00',
            'new_clock_out' => '12:00',
            'attendance_record_id' => $attendanceRecord->id,
            'comment' => '修正理由テスト',
            'application_date' => now()
        ]);

        $response = $this->get('/stamp_correction_request/approve/' . $application->id);

        $response->assertSee($application->new_clock_in);
        $response->assertSee($application->new_clock_out);
        $response->assertSee($application->comment);
    }

    /** @test */
    public function it_should_correctly_process_modification_request_approval()
    {
        $admin = User::where('admin_status', true)->first();
        $this->actingAs($admin);

        $user = User::where('admin_status', false)->first();

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => now(),
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);

        $application = Application::create([
            'approval_status' => '承認済み',
            'user_id' => $user->id,
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => '10:00',
            'new_clock_out' => '12:00',
            'attendance_record_id' => $attendanceRecord->id,
            'comment' => '修正理由テスト',
            'application_date' => now()
        ]);

        $response = $this->get('/stamp_correction_request/approve/' . $application->id);
        $response = $this->post('/stamp_correction_request/approve/' . $application->id);


        $response = $this->get('/attendance/' . $attendanceRecord->id);
        $response->assertSee('10:00');
        $response->assertSee('12:00');
    }
}
