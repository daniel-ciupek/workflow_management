<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $pin = '';

    public function authenticate(): void
    {
        $key = 'login.' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('pin', "Too many login attempts. Please try again in {$seconds} seconds.");
            return;
        }

        $len = strlen($this->pin);

        // Admin — 6-digit PIN
        if ($len === 6 && ctype_digit($this->pin)) {
            $user = \App\Models\User::where('pin', $this->pin)->where('role', 'admin')->first();

            if (!$user) {
                RateLimiter::hit($key, 60);
                $this->addError('pin', 'Invalid PIN. Please try again.');
                $this->pin = '';
                return;
            }

            RateLimiter::clear($key);
            Auth::login($user);
            $this->redirect(route('admin.dashboard'));
            return;
        }

        // Employee — global 4-digit PIN
        if ($len === 4 && ctype_digit($this->pin)) {
            $employeePin = Setting::get('employee_pin');

            if (!$employeePin || $this->pin !== $employeePin) {
                RateLimiter::hit($key, 60);
                $this->addError('pin', 'Invalid PIN. Please try again.');
                $this->pin = '';
                return;
            }

            RateLimiter::clear($key);
            session()->put('employee_access', true);
            $this->redirect(route('employee.dashboard'));
            return;
        }

        RateLimiter::hit($key, 60);
        $this->addError('pin', 'Invalid PIN. Enter 4 digits (employees) or 6 digits (admin).');
        $this->pin = '';
    }
}; ?>

<div class="w-full">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-base-content">Workflow Management</h1>
        <p class="text-base-content/60 mt-1 text-sm">Enter your PIN to sign in</p>
    </div>

    <form wire:submit="authenticate" class="space-y-5">
        <div class="form-control">
            <label class="label" for="pin">
                <span class="label-text font-medium">PIN</span>
            </label>
            <input
                wire:model="pin"
                id="pin"
                type="password"
                inputmode="numeric"
                maxlength="6"
                placeholder="Enter your PIN"
                class="input input-bordered w-full text-center tracking-widest text-lg @error('pin') input-error @enderror"
                autofocus
                autocomplete="off"
            />
            @error('pin')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign In</span>
            <span wire:loading>
                <span class="loading loading-spinner loading-sm"></span>
                Signing in...
            </span>
        </button>
    </form>

    <p class="text-center text-xs text-base-content/40 mt-6">
        4-digit PIN for employees &bull; 6-digit PIN for administrators
    </p>
</div>
