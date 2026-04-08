<x-layouts.app>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Dashboard</h2>
    </div>

    @php
        $employeeCount = \App\Models\User::where('role', 'employee')->count();
        $taskCount = \App\Models\Task::count();
        $doneCount = \App\Models\Task::withCount([
                'users',
                'users as done_count' => fn($q) => $q->where('task_user.done', true),
            ])->get()->filter(fn($t) => $t->users_count > 0 && $t->done_count === $t->users_count)->count();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <a href="{{ route('admin.employees') }}" class="card bg-base-100 shadow hover:shadow-md transition-shadow">
            <div class="card-body">
                <h3 class="card-title text-sm text-base-content/60">Employees</h3>
                <p class="text-4xl font-bold text-primary">{{ $employeeCount }}</p>
            </div>
        </a>
        <a href="{{ route('admin.tasks') }}" class="card bg-base-100 shadow hover:shadow-md transition-shadow">
            <div class="card-body">
                <h3 class="card-title text-sm text-base-content/60">Total Tasks</h3>
                <p class="text-4xl font-bold text-primary">{{ $taskCount }}</p>
            </div>
        </a>
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title text-sm text-base-content/60">Fully Completed</h3>
                <p class="text-4xl font-bold text-success">{{ $doneCount }}</p>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary">+ New Task</a>
        <a href="{{ route('admin.employees') }}" class="btn btn-ghost">Manage Employees</a>
    </div>
</x-layouts.app>
