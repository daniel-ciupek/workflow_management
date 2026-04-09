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

<div class="space-y-8">

    {{-- Admin PIN --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <h3 class="font-semibold text-lg">Admin PIN <span class="text-base-content/40 text-sm font-normal">(6 digits)</span></h3>

            @if($admin_success)
                <div class="alert alert-success">
                    <span>Admin PIN changed successfully.</span>
                </div>
            @endif

            <form wire:submit="saveAdminPin" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Current PIN</span></label>
                    <input wire:model="current_pin" type="password" inputmode="numeric" maxlength="6"
                           class="input input-bordered @error('current_pin') input-error @enderror" autocomplete="off" />
                    @error('current_pin') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">New PIN</span></label>
                    <input wire:model="new_pin" type="password" inputmode="numeric" maxlength="6"
                           class="input input-bordered @error('new_pin') input-error @enderror" autocomplete="off" />
                    @error('new_pin') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Confirm New PIN</span></label>
                    <input wire:model="confirm_pin" type="password" inputmode="numeric" maxlength="6"
                           class="input input-bordered @error('confirm_pin') input-error @enderror" autocomplete="off" />
                    @error('confirm_pin') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveAdminPin">
                        <span wire:loading.remove wire:target="saveAdminPin">Save Admin PIN</span>
                        <span wire:loading wire:target="saveAdminPin"><span class="loading loading-spinner loading-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Global Employee PIN — super admin only --}}
    @if(auth()->user()->isSuperAdmin())
    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <h3 class="font-semibold text-lg">Employee PIN <span class="text-base-content/40 text-sm font-normal">(4 digits · shared by all employees)</span></h3>

            @if($employee_success)
                <div class="alert alert-success">
                    <span>Employee PIN updated successfully.</span>
                </div>
            @endif

            <form wire:submit="saveEmployeePin" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">New Employee PIN</span></label>
                    <input wire:model="new_employee_pin" type="password" inputmode="numeric" maxlength="4"
                           class="input input-bordered @error('new_employee_pin') input-error @enderror" autocomplete="off" />
                    @error('new_employee_pin') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Confirm Employee PIN</span></label>
                    <input wire:model="confirm_employee_pin" type="password" inputmode="numeric" maxlength="4"
                           class="input input-bordered @error('confirm_employee_pin') input-error @enderror" autocomplete="off" />
                    @error('confirm_employee_pin') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveEmployeePin">
                        <span wire:loading.remove wire:target="saveEmployeePin">Save Employee PIN</span>
                        <span wire:loading wire:target="saveEmployeePin"><span class="loading loading-spinner loading-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @endif

    <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
</div>
