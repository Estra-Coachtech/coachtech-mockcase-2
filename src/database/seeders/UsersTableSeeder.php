<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'ユーザー１',
                'email' => 'user1@example.com',
                'password' => bcrypt('password'),
                'admin_status' => false,
                'attendance_status' => '勤務外'
            ],
            [
                'name' => 'ユーザー２',
                'email' => 'user2@example.com',
                'password' => bcrypt('password'),
                'admin_status' => false,
                'attendance_status' => '勤務外'
            ],
            [
                'name' => 'ユーザー３',
                'email' => 'user3@example.com',
                'password' => bcrypt('password'),
                'admin_status' => true,
                'attendance_status' => '勤務外'
            ]
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }
}
