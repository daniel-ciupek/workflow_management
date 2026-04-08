<?php

use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public bool $confirmDelete = false;
    public ?int $deletingId = null;

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->confirmDelete = true;
    }

    public function destroy(): void
    {
        if (!$this->deletingId) return;

        $task = Task::findOrFail($this->deletingId);

        // Remove attachments from storage
        if (!empty($task->attachments)) {
            foreach ($task->attachments as $path) {
                Storage::disk('tasks')->delete($path);
            }
            // Remove task directory if empty
            Storage::disk('tasks')->deleteDirectory("task-{$task->id}");
        }

        $task->delete();

        $this->confirmDelete = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    public function closeModal(): void
    {
        $this->confirmDelete = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        return [
            'tasks' => Task::withCount([
                    'users',
                    'users as done_count' => fn ($q) => $q->where('task_user.done', true),
                ])
                ->with(['users' => fn ($q) => $q->where('task_user.done', true)->select('users.id', 'users.name', 'task_user.completed_at')])
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Tasks</h2>
        <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary btn-sm">+ New Task</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="space-y-4">
        @forelse($tasks as $task)
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-semibold text-lg">{{ $task->title }}</h3>
                                <span class="badge badge-ghost badge-sm">
                                    {{ $task->done_count }} / {{ $task->users_count }} done
                                </span>
                                @if($task->users_count > 0 && $task->done_count === $task->users_count)
                                    <span class="badge badge-success badge-sm">Completed</span>
                                @endif
                            </div>

                            @if($task->description)
                                <p class="text-sm text-base-content/60 mt-1 line-clamp-2">{{ $task->description }}</p>
                            @endif

                            <div class="text-xs text-base-content/40 mt-2">
                                Created {{ $task->created_at->format('d M Y') }}
                                @if(!empty($task->attachments))
                                    &bull; {{ count($task->attachments) }} attachment(s)
                                @endif
                            </div>

                            {{-- Progress bar --}}
                            @if($task->users_count > 0)
                                <div class="mt-3">
                                    <progress class="progress progress-success w-full max-w-xs"
                                              value="{{ $task->done_count }}"
                                              max="{{ $task->users_count }}"></progress>
                                </div>
                            @endif

                            {{-- Who completed --}}
                            @if($task->users->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($task->users as $user)
                                        <span class="badge badge-outline badge-sm text-success">
                                            {{ $user->name }}
                                            @if($user->pivot->completed_at)
                                                · {{ \Carbon\Carbon::parse($user->pivot->completed_at)->format('d M') }}
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <button wire:click="confirmDelete({{ $task->id }})" class="btn btn-ghost btn-sm text-error shrink-0">Delete</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card bg-base-100 shadow">
                <div class="card-body text-center text-base-content/50 py-12">
                    No tasks yet. <a href="{{ route('admin.tasks.create') }}" class="link link-primary">Create the first one.</a>
                </div>
            </div>
        @endforelse
    </div>

    @if($tasks->hasPages())
        <div class="mt-4">{{ $tasks->links() }}</div>
    @endif

    {{-- Confirm Delete --}}
    @if($confirmDelete)
    <div class="modal modal-open">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete Task</h3>
            <p class="py-4 text-base-content/70">This will permanently delete the task and all its attachments. Employees will lose access to it.</p>
            <div class="modal-action">
                <button wire:click="closeModal" class="btn btn-ghost">Cancel</button>
                <button wire:click="destroy" class="btn btn-error" wire:loading.attr="disabled">Delete</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModal"></div>
    </div>
    @endif
</div>
