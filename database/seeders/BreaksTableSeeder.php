<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use Faker\Factory as Faker;
use Carbon\Carbon;

class BreaksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // user1 のレコードは AttendanceRecordsTableSeeder で固定休憩 (12:00-13:00) を
        // 既に付与しているため、ここでは休憩がまだ無いレコード（user2/user3 のランダム）のみに付与する。
        AttendanceRecord::query()->whereDoesntHave('breaks')->each(function (AttendanceRecord $record) use ($faker) {
            $clockIn  = Carbon::parse($record->clock_in);
            $clockOut = Carbon::parse(
                $record->clock_out ?? $clockIn->copy()->addHours(8)
            );

            // 万一 start > end なら入れ替え
            if ($clockIn->gt($clockOut)) {
                [$clockIn, $clockOut] = [$clockOut, $clockIn];
            }

            $breakCount = rand(0, 5);

            for ($i = 0; $i < $breakCount; $i++) {
                $startStr = $clockIn->format('Y-m-d H:i:s');
                $endStr   = $clockOut->format('Y-m-d H:i:s');
                $in = $faker->dateTimeBetween($startStr, $endStr);
                $inStr = $in->format('Y-m-d H:i:s');
                $out   = $faker->dateTimeBetween($inStr, $endStr);

                AttendanceBreak::create([
                    'attendance_record_id' => $record->id,
                    'break_in'             => Carbon::instance($in)->format('H:i:s'),
                    'break_out'            => Carbon::instance($out)->format('H:i:s'),
                ]);
            }
        });
    }
}
