<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {

    public function select(int $id): void
    {
        $employee = User::where('id', $id)->where('role', 'employee')->firstOrFail();
        session()->put('employee_id', $employee->id);
        session()->put('employee_name', $employee->name);
        $this->redirect(route('employee.dashboard'));
    }

    public function with(): array
    {
        return [
            'employees' => User::where('role', 'employee')->orderBy('name')->get(['id', 'name']),
        ];
    }
}; ?>

<div class="min-h-[70vh] flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Who are you?</h1>
            <p class="text-slate-500 text-sm mt-1">Select your name to continue</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden" style="box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);">
            @foreach($employees as $employee)
                <button wire:click="select({{ $employee->id }})"
                        class="w-full flex items-center gap-3 px-5 py-4 text-left hover:bg-slate-50 transition-colors duration-150 border-b border-slate-100 last:border-b-0 active:bg-slate-100">
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <span class="text-sm font-semibold text-blue-700">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                    </div>
                    <span class="text-sm font-medium text-slate-800">{{ $employee->name }}</span>
                    <svg class="w-4 h-4 text-slate-300 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @endforeach
        </div>
    </div>
</div>
