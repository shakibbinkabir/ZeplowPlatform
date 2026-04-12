<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'shakib@zeplow.com'],
            [
                'name' => 'Shakib Bin Kabir',
                'password' => Hash::make('CHANGE_ME_ON_FIRST_LOGIN'),
                'role' => 'super_admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'shadman@zeplow.com'],
            [
                'name' => 'Shadman Sakib',
                'password' => Hash::make('CHANGE_ME_ON_FIRST_LOGIN'),
                'role' => 'admin',
            ]
        );
    }
}
