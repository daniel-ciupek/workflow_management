<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['name' => 'Admin', 'role' => 'admin', 'is_super' => true],
            [
                'name'     => 'Admin',
                'pin'      => '000000',
                'role'     => 'admin',
                'is_super' => true,
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'employee_pin'],
            ['value' => Hash::make('1234')]
        );
    }
}
