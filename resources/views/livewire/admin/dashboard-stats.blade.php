<?php

use App\Models\Task;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function with(): array
    {
        $adminId       = auth()->id();
        $employeeCount = auth()->user()->isSuperAdmin()
            ? User::where('role', 'employee')->count()
            : auth()->user()->employees()->count();
        // employees() uses admin_employee pivot — consistent with the employees list page
        $taskCount = Task::where('created_by', $adminId)->active()->count();

        return compact('employeeCount', 'taskCount');
    }
}; ?>

<div class="page-enter">
    {{-- Page header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard</h1>
        <p class="text-slate-500 text-sm mt-1">Overview of your workforce and tasks</p>
    </div>

    {{-- Stats grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
        {{-- Employees card --}}
        <a href="{{ route('admin.employees') }}"
           class="stat-card group bg-white rounded-xl p-6 border border-slate-200 block"
           style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06);">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Employees</p>
                    <p class="text-4xl font-bold text-slate-900 tabular-nums">{{ $employeeCount }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 group-hover:bg-blue-100 rounded-xl flex items-center justify-center transition-colors duration-200 shrink-0" style="transition: background-color 200ms ease;">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-400">Total team members</p>
                <p class="text-xs text-blue-600 font-semibold flex items-center gap-1">
                    Manage
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </p>
            </div>
        </a>

        {{-- Tasks card --}}
        <a href="{{ route('admin.tasks') }}"
           class="stat-card group bg-white rounded-xl p-6 border border-slate-200 block"
           style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06);">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Active Tasks</p>
                    <p class="text-4xl font-bold text-slate-900 tabular-nums">{{ $taskCount }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 group-hover:bg-blue-100 rounded-xl flex items-center justify-center transition-colors duration-200 shrink-0" style="transition: background-color 200ms ease;">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-400">Currently in progress</p>
                <p class="text-xs text-blue-600 font-semibold flex items-center gap-1">
                    View all
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </p>
            </div>
        </a>
    </div>

    {{-- Quick actions --}}
    <div class="bg-white rounded-xl p-6 border border-slate-200" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06);">
        <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.tasks.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Task
            </a>
            <a href="{{ route('admin.employees') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Manage Employees
            </a>
        </div>
    </div>
</div>
