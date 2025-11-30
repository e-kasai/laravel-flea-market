<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $users = [
            [
                'name'              => 'admin',
                'email'             => 'admin_user@example.com',
                'password'          => Hash::make('password'),
                'email_verified_at' => $now,   // 管理者は認証済み
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'name'       => 'userA',
                'email'      => 'userA@example.com',
                'password'   => Hash::make('password'),
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'userB',
                'email'      => 'userB@example.com',
                'password'   => Hash::make('password'),
                'email_verified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'userC',
                'email'      => 'userC@example.com',
                'password'   => Hash::make('password'),
                'email_verified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        DB::table('users')->insert($users);
    }
}
