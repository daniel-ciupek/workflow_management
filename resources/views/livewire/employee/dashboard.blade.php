<?php

use App\Models\Task;
use Livewire\Volt\Component;

new class extends Component {

    public function markDone(int $taskId): void
    {
        $user = auth()->user();

        $pivot = $user->tasks()->where('tasks.id', $taskId)->first();

        if (!$pivot || $pivot->pivot->done) {
            return;
        }

        $user->tasks()->updateExistingPivot($taskId, [
            'done'         => true,
            'completed_at' => now(),
        ]);
    }

    public function with(): array
    {
        $user = auth()->user();

        $activeTasks = $user->tasks()
            ->wherePivot('done', false)
            ->select('tasks.id', 'tasks.title', 'tasks.created_at')
            ->orderBy('tasks.created_at')
            ->get();

        $completedTasks = $user->tasks()
            ->wherePivot('done', true)
            ->select('tasks.id', 'tasks.title', 'task_user.completed_at')
            ->orderByPivot('completed_at', 'desc')
            ->limit(10)
            ->get();

        return compact('activeTasks', 'completedTasks');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">My Tasks</h2>
        <span class="badge badge-ghost">{{ $activeTasks->count() }} pending</span>
    </div>

    {{-- Active tasks grid --}}
    @if($activeTasks->isEmpty())
        <div class="card bg-base-100 shadow">
            <div class="card-body text-center text-base-content/50 py-12">
                No pending tasks. Great job!
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            @foreach($activeTasks as $task)
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title text-base">{{ $task->title }}</h3>
                        <p class="text-xs text-base-content/50 mt-auto pt-2">
                            Assigned {{ $task->created_at->format('d M Y') }}
                        </p>
                        <div class="card-actions justify-end mt-3">
                            <button
                                wire:click="markDone({{ $task->id }})"
                                wire:loading.attr="disabled"
                                wire:target="markDone({{ $task->id }})"
                                class="btn btn-success btn-sm w-full"
                            >
                                <span wire:loading.remove wire:target="markDone({{ $task->id }})">Mark as Done</span>
                                <span wire:loading wire:target="markDone({{ $task->id }})">Saving...</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Completed tasks (collapsible) --}}
    @if($completedTasks->isNotEmpty())
        <div x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center gap-2 text-sm font-medium text-base-content/70 hover:text-base-content mb-3 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                Completed Tasks ({{ $completedTasks->count() }})
            </button>

            <div x-show="open" x-transition>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($completedTasks as $task)
                        <div class="card bg-base-100 shadow opacity-60">
                            <div class="card-body">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="card-title text-base line-through text-base-content/50">{{ $task->title }}</h3>
                                    <span class="badge badge-success badge-sm shrink-0">Done</span>
                                </div>
                                <p class="text-xs text-base-content/40 mt-auto pt-2">
                                    Completed {{ \Carbon\Carbon::parse($task->pivot->completed_at)->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
