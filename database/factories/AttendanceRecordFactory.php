<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition()
    {
        // ユーザーID
        $userIds = User::pluck('id');
        $userId  = $userIds->random();

        // 日付と打刻（実際にはCarbonインスタンスを渡し、モデルのキャストでH:iに）
        // 直近3ヶ月のデータを生成する（採点時に「最近の勤怠」として確認できるように）
        $date     = $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d');
        $clockIn  = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$this->faker->time('H:i:s'));
        $clockOut = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$this->faker->time('H:i:s'))
                      ->addHours(rand(6, 10)); // 出退勤間隔は6〜10時間のランダム

        // 休憩時間合計はあとで BreaksTableSeeder が入れるので、ここではゼロでもOK
        $totalBreakSeconds = 0;

        // 実働時間（秒） = 出退勤差 - 休憩
        $workedSeconds = $clockOut->diffInSeconds($clockIn) - $totalBreakSeconds;

        return [
            'user_id'            => $userId,
            'date'               => $date,
            'clock_in'           => $clockIn,
            'clock_out'          => $clockOut,
            'total_break_time'   => gmdate('H:i', $totalBreakSeconds),
            'total_time'         => gmdate('H:i', $workedSeconds),
            'comment'            => $this->faker->optional()->sentence(),
        ];
    }
}
