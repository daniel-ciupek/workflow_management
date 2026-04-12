<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hash all plaintext admin PINs that are not already hashed
        DB::table('users')
            ->where('role', 'admin')
            ->whereNotNull('pin')
            ->orderBy('id')
            ->each(function ($user) {
                // Bcrypt hashes start with $2y$ — skip if already hashed
                if (!str_starts_with($user->pin, '$2y$') && !str_starts_with($user->pin, '$2b$')) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['pin' => Hash::make($user->pin)]);
                }
            });

        // Hash the employee PIN in settings if stored in plaintext
        $setting = DB::table('settings')->where('key', 'employee_pin')->first();
        if ($setting && !str_starts_with($setting->value, '$2y$') && !str_starts_with($setting->value, '$2b$')) {
            DB::table('settings')
                ->where('key', 'employee_pin')
                ->update(['value' => Hash::make($setting->value)]);
        }

        // Remove unique constraint on pin — bcrypt salted hashes are always unique
        // but two identical PINs would have different hashes, so uniqueness is no longer enforceable
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pin']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('pin');
        });
    }
};
