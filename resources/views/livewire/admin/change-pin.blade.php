<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    // Admin PIN change
    public string $current_pin = '';
    public string $new_pin = '';
    public string $confirm_pin = '';
    public bool $admin_success = false;

    // Global employee PIN
    public string $new_employee_pin = '';
    public string $confirm_employee_pin = '';
    public bool $employee_success = false;

    public function saveAdminPin(): void
    {
        $this->admin_success = false;

        $user = Auth::user();

        if ($this->current_pin !== $user->pin) {
            $this->addError('current_pin', 'Current PIN is incorrect.');
            $this->current_pin = '';
            return;
        }

        if (strlen($this->new_pin) !== 6 || !ctype_digit($this->new_pin)) {
            $this->addError('new_pin', 'New PIN must be exactly 6 digits.');
            return;
        }

        if ($this->new_pin !== $this->confirm_pin) {
            $this->addError('confirm_pin', 'PINs do not match.');
            $this->confirm_pin = '';
            return;
        }

        if ($this->new_pin === $this->current_pin) {
            $this->addError('new_pin', 'New PIN must be different from the current PIN.');
            return;
        }

        $user->update(['pin' => $this->new_pin]);

        $this->current_pin = '';
        $this->new_pin = '';
        $this->confirm_pin = '';
        $this->admin_success = true;
    }

    public function saveEmployeePin(): void
    {
        $this->employee_success = false;

        if (strlen($this->new_employee_pin) !== 4 || !ctype_digit($this->new_employee_pin)) {
            $this->addError('new_employee_pin', 'Employee PIN must be exactly 4 digits.');
            return;
        }

        if ($this->new_employee_pin !== $this->confirm_employee_pin) {
            $this->addError('confirm_employee_pin', 'PINs do not match.');
            $this->confirm_employee_pin = '';
            return;
        }

        Setting::set('employee_pin', $this->new_employee_pin);

        $this->new_employee_pin = '';
        $this->confirm_employee_pin = '';
        $this->employee_success = true;
    }
}; ?>

<div class="max-w-xl page-enter">
    {{-- Page header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.dashboard') }}"
           class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
            <p class="text-slate-500 text-sm mt-0.5">Manage PINs for access control</p>
        </div>
    </div>

    <div class="space-y-5">
        {{-- Admin PIN --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Admin PIN</h2>
                <p class="text-xs text-slate-500 mt-0.5">6-digit PIN used to sign in as administrator</p>
            </div>
            <div class="px-6 py-5">
                @if($admin_success)
                    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 text-sm">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Admin PIN changed successfully.
                    </div>
                @endif

                <form wire:submit="saveAdminPin" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Current PIN</label>
                        <input wire:model="current_pin" type="password" inputmode="numeric" maxlength="6"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('current_pin') border-red-400 bg-red-50 @enderror"
                               placeholder="••••••" autocomplete="off" />
                        @error('current_pin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">New PIN</label>
                        <input wire:model="new_pin" type="password" inputmode="numeric" maxlength="6"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('new_pin') border-red-400 bg-red-50 @enderror"
                               placeholder="••••••" autocomplete="off" />
                        @error('new_pin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm New PIN</label>
                        <input wire:model="confirm_pin" type="password" inputmode="numeric" maxlength="6"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('confirm_pin') border-red-400 bg-red-50 @enderror"
                               placeholder="••••••" autocomplete="off" />
                        @error('confirm_pin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="pt-1">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60"
                                wire:loading.attr="disabled" wire:target="saveAdminPin">
                            <span wire:loading.remove wire:target="saveAdminPin">Save Admin PIN</span>
                            <span wire:loading wire:target="saveAdminPin" class="flex items-center gap-1.5">
                                <span class="loading loading-spinner loading-sm"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Global Employee PIN — super admin only --}}
        @if(auth()->user()->isSuperAdmin())
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Employee PIN</h2>
                <p class="text-xs text-slate-500 mt-0.5">4-digit shared PIN used by all employees to sign in</p>
            </div>
            <div class="px-6 py-5">
                @if($employee_success)
                    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 text-sm">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Employee PIN updated successfully.
                    </div>
                @endif

                <form wire:submit="saveEmployeePin" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">New Employee PIN</label>
                        <input wire:model="new_employee_pin" type="password" inputmode="numeric" maxlength="4"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('new_employee_pin') border-red-400 bg-red-50 @enderror"
                               placeholder="••••" autocomplete="off" />
                        @error('new_employee_pin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Employee PIN</label>
                        <input wire:model="confirm_employee_pin" type="password" inputmode="numeric" maxlength="4"
                               class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('confirm_employee_pin') border-red-400 bg-red-50 @enderror"
                               placeholder="••••" autocomplete="off" />
                        @error('confirm_employee_pin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="pt-1">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60"
                                wire:loading.attr="disabled" wire:target="saveEmployeePin">
                            <span wire:loading.remove wire:target="saveEmployeePin">Save Employee PIN</span>
                            <span wire:loading wire:target="saveEmployeePin" class="flex items-center gap-1.5">
                                <span class="loading loading-spinner loading-sm"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
