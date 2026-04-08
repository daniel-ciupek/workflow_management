<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

?>

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
                class="input input-bordered w-full text-center tracking-widest text-lg <?php $__errorArgs = ['pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                autofocus
                autocomplete="off"
            />
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <label class="label">
                    <span class="label-text-alt text-error"><?php echo e($message); ?></span>
                </label>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
</div><?php /**PATH /var/www/html/resources/views/livewire/pages/auth/login.blade.php ENDPATH**/ ?>