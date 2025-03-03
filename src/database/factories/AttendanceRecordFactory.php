<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $userIds = User::pluck('id');

        $date = $this->faker->dateTimeBetween('2024-01-01', '2024-12-31')->format('Y-m-d');

        $clock_in = \DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $this->faker->time('H:i:s'));
        $clock_out = $this->faker->dateTimeBetween($clock_in, \DateTime::createFromFormat('Y-m-d H:i:s', $date . ' 24:00:00'));

        $break_time_limit = 2 * 60 * 60;
        $break_in = $this->faker->dateTimeBetween($clock_in, $clock_out);
        $break_out = $this->faker->dateTimeBetween($break_in, $clock_out);
        $break2_in = $this->faker->dateTimeBetween($break_out, $clock_out);
        $break2_out = $this->faker->dateTimeBetween($break2_in, $clock_out);

        $first_break_duration = $break_out->getTimestamp() - $break_in->getTimestamp();
        $second_break_duration = $break2_out->getTimestamp() - $break2_in->getTimestamp();

        $total_break_time = min($first_break_duration + $second_break_duration, $break_time_limit);

        $total_time = $clock_out->getTimestamp() - $clock_in->getTimestamp() - $total_break_time;

        return [
            'user_id' => $userIds->random(),
            'date' => $date,
            'clock_in' => $clock_in,
            'clock_out' => $clock_out,
            'break_in' => $break_in,
            'break_out' => $break_out,
            'break2_in' => $break2_in,
            'break2_out' => $break2_out,
            'total_time' => gmdate('H:i', $total_time),
            'total_break_time' => gmdate('H:i', $total_break_time),
        ];
    }
}