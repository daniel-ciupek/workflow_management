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
            session()->forget(['employee_id', 'employee_name']);
            $this->redirect(route('employee.select'));
            return;
        }

        RateLimiter::hit($key, 60);
        $this->addError('pin', 'Invalid PIN. Enter 4 digits (employees) or 6 digits (admin).');
        $this->pin = '';
    }
}; ?>

<div class="w-full">
    {{-- Card --}}
    <div class="bg-white rounded-2xl p-8" style="box-shadow: 0 8px 32px 0 rgba(0,0,0,0.10), 0 2px 6px 0 rgba(0,0,0,0.06); border: 1px solid rgba(226,232,240,0.8);">

        {{-- Logo + Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-600 rounded-2xl mb-5" style="box-shadow: 0 4px 14px 0 rgba(37,99,235,0.35);">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Workflow</h1>
            <p class="text-slate-500 mt-1.5 text-sm">Enter your PIN to continue</p>
        </div>

        <form wire:submit="authenticate" class="space-y-5">
            <div>
                <label for="pin" class="block text-sm font-medium text-slate-700 mb-1.5 text-center">PIN</label>
                <input
                    wire:model="pin"
                    id="pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="••••••"
                    class="block w-full rounded-lg border text-center tracking-[0.5em] text-lg font-mono py-3 px-4 transition-colors duration-150 outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 {{ $errors->has('pin') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 bg-white focus:border-blue-500' }}"
                    autofocus
                    autocomplete="off"
                />
                @error('pin')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Sign In</span>
                <span wire:loading class="flex items-center gap-2">
                    <span class="loading loading-spinner loading-sm"></span>
                    Signing in...
                </span>
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6">
            4-digit PIN for employees &bull; 6-digit PIN for administrators
        </p>
    </div>
</div>
