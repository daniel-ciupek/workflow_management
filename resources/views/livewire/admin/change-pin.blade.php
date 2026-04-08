<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_pin = '';
    public string $new_pin = '';
    public string $confirm_pin = '';
    public bool $success = false;

    public function save(): void
    {
        $this->success = false;

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
        $this->success = true;
    }
}; ?>

<div class="card bg-base-100 shadow">
    <div class="card-body space-y-4">

        @if($success)
            <div class="alert alert-success">
                <span>PIN changed successfully.</span>
            </div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <div class="form-control">
                <label class="label" for="current_pin">
                    <span class="label-text font-medium">Current PIN</span>
                </label>
                <input
                    wire:model="current_pin"
                    id="current_pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    class="input input-bordered @error('current_pin') input-error @enderror"
                    autocomplete="off"
                />
                @error('current_pin')
                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label" for="new_pin">
                    <span class="label-text font-medium">New PIN</span>
                </label>
                <input
                    wire:model="new_pin"
                    id="new_pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    class="input input-bordered @error('new_pin') input-error @enderror"
                    autocomplete="off"
                />
                @error('new_pin')
                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                @enderror
            </div>

            <div class="form-control">
                <label class="label" for="confirm_pin">
                    <span class="label-text font-medium">Confirm New PIN</span>
                </label>
                <input
                    wire:model="confirm_pin"
                    id="confirm_pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    class="input input-bordered @error('confirm_pin') input-error @enderror"
                    autocomplete="off"
                />
                @error('confirm_pin')
                    <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save PIN</span>
                    <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
