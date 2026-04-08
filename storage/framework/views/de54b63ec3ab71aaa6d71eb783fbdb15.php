<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Dashboard</h2>
    </div>

    <?php
        $employeeCount = \App\Models\User::where('role', 'employee')->count();
        $taskCount = \App\Models\Task::count();
        $doneCount = \App\Models\Task::withCount([
                'users',
                'users as done_count' => fn($q) => $q->where('task_user.done', true),
            ])->get()->filter(fn($t) => $t->users_count > 0 && $t->done_count === $t->users_count)->count();
    ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <a href="<?php echo e(route('admin.employees')); ?>" class="card bg-base-100 shadow hover:shadow-md transition-shadow">
            <div class="card-body">
                <h3 class="card-title text-sm text-base-content/60">Employees</h3>
                <p class="text-4xl font-bold text-primary"><?php echo e($employeeCount); ?></p>
            </div>
        </a>
        <a href="<?php echo e(route('admin.tasks')); ?>" class="card bg-base-100 shadow hover:shadow-md transition-shadow">
            <div class="card-body">
                <h3 class="card-title text-sm text-base-content/60">Total Tasks</h3>
                <p class="text-4xl font-bold text-primary"><?php echo e($taskCount); ?></p>
            </div>
        </a>
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title text-sm text-base-content/60">Fully Completed</h3>
                <p class="text-4xl font-bold text-success"><?php echo e($doneCount); ?></p>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <a href="<?php echo e(route('admin.tasks.create')); ?>" class="btn btn-primary">+ New Task</a>
        <a href="<?php echo e(route('admin.employees')); ?>" class="btn btn-ghost">Manage Employees</a>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>