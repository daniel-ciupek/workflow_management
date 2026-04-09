<?php

use App\Models\Task;
use Livewire\Volt\Component;

new class extends Component {

    public function with(): array
    {
        $tasks = Task::with(['users' => fn ($q) => $q->select('users.id', 'users.name')])
            ->select('tasks.id', 'tasks.title', 'tasks.created_at')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        return compact('tasks');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Tasks</h2>
        <span class="badge badge-ghost">{{ $tasks->count() }} total</span>
    </div>

    @if($tasks->isEmpty())
        <div class="card bg-base-100 shadow">
            <div class="card-body text-center text-base-content/50 py-12">
                No tasks available.
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($tasks as $task)
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title text-base">{{ $task->title }}</h3>
                        <p class="text-xs text-base-content/50 mt-1">
                            Added {{ $task->created_at->format('d M Y') }}
                        </p>
                        @if($task->users->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-3">
                                @foreach($task->users as $user)
                                    <span class="badge badge-ghost badge-sm">{{ $user->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
