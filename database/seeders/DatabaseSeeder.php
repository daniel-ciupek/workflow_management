<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['pin' => '000000'],
            [
                'name' => 'Admin',
                'pin'  => '000000',
                'role' => 'admin',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'employee_pin'],
            ['value' => '1234']
        );
    }
}
