<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="font-sans antialiased bg-base-200 min-h-screen">

    <div class="navbar bg-base-100 shadow-sm px-4 sticky top-0 z-50">
        <div class="navbar-start gap-4">
            <span class="text-lg font-bold text-primary">Workflow Management</span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->isAdmin()): ?>
                    <nav class="hidden md:flex gap-1">
                        <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-ghost btn-sm <?php echo e(request()->routeIs('admin.dashboard') ? 'btn-active' : ''); ?>">Dashboard</a>
                        <a href="<?php echo e(route('admin.employees')); ?>" class="btn btn-ghost btn-sm <?php echo e(request()->routeIs('admin.employees') ? 'btn-active' : ''); ?>">Employees</a>
                        <a href="<?php echo e(route('admin.tasks')); ?>" class="btn btn-ghost btn-sm <?php echo e(request()->routeIs('admin.tasks*') ? 'btn-active' : ''); ?>">Tasks</a>
                    </nav>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="navbar-end gap-2">
            <span class="text-sm text-base-content/70 hidden sm:inline">
                <?php echo e(auth()->user()->name); ?>

                <span class="badge badge-ghost badge-sm ml-1"><?php echo e(ucfirst(auth()->user()->role)); ?></span>
            </span>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->isAdmin()): ?>
                <a href="<?php echo e(route('admin.change-pin')); ?>" class="btn btn-ghost btn-sm">Change PIN</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-ghost btn-sm text-error">Sign Out</button>
            </form>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php echo e($slot); ?>

    </main>

</body>
</html>
<?php /**PATH /var/www/html/resources/views/components/layouts/app.blade.php ENDPATH**/ ?>