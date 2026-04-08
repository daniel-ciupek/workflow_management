<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

?>

<div class="card bg-base-100 shadow">
    <div class="card-body space-y-4">

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($success): ?>
            <div class="alert alert-success">
                <span>PIN changed successfully.</span>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

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
                    class="input input-bordered <?php $__errorArgs = ['current_pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    autocomplete="off"
                />
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['current_pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <label class="label"><span class="label-text-alt text-error"><?php echo e($message); ?></span></label>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                    class="input input-bordered <?php $__errorArgs = ['new_pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    autocomplete="off"
                />
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['new_pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <label class="label"><span class="label-text-alt text-error"><?php echo e($message); ?></span></label>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                    class="input input-bordered <?php $__errorArgs = ['confirm_pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    autocomplete="off"
                />
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['confirm_pin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <label class="label"><span class="label-text-alt text-error"><?php echo e($message); ?></span></label>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save PIN</span>
                    <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                </button>
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div><?php /**PATH /var/www/html/resources/views/livewire/admin/change-pin.blade.php ENDPATH**/ ?>