<?php

use App\Models\Task;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {

    public function with(): array
    {
        $adminId       = auth()->id();
        $employeeCount = auth()->user()->isSuperAdmin()
            ? User::where('role', 'employee')->count()
            : auth()->user()->employees()->count();
        // employees() uses admin_employee pivot — consistent with the employees list page
        $taskCount = Task::where('created_by', $adminId)->count();

        return compact('employeeCount', 'taskCount');
    }
}; ?>

<div class="max-w-2xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
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
    </div>

    <div class="flex gap-3 justify-center">
        <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary">+ New Task</a>
        <a href="{{ route('admin.employees') }}" class="btn btn-ghost">Manage Employees</a>
    </div>
</div>
