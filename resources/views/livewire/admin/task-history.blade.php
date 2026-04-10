<?php

use App\Models\Task;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public bool $showViewModal = false;
    public ?int $viewingId = null;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function openView(int $id): void
    {
        $this->viewingId = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function destroy(): void
    {
        if ($this->deletingId) {
            Task::findOrFail($this->deletingId)->delete();
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    public function closeModals(): void
    {
        $this->showViewModal = false;
        $this->showDeleteModal = false;
        $this->viewingId = null;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $tasks = Task::archived()
            ->with(['users' => fn ($q) => $q->select('users.id', 'users.name'), 'creator'])
            ->where('created_by', auth()->id())
            ->latest('archived_at')
            ->paginate(15);

        $viewingTask = $this->viewingId
            ? Task::with(['users' => fn ($q) => $q->withPivot('done', 'completed_at')->select('users.id', 'users.name')])->find($this->viewingId)
            : null;

        return compact('tasks', 'viewingTask');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Task History</h2>
        <span class="badge badge-ghost">Archived — older than 48h</span>
    </div>

    @if($tasks->isEmpty())
        <div class="card bg-base-100 shadow text-center text-base-content/50 py-12">
            No archived tasks yet.
        </div>
    @else
        <div class="card bg-base-100 shadow overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assigned to</th>
                        <th>Archived</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                        <tr>
                            <td class="font-medium">{{ $task->title }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($task->users as $user)
                                        <span class="badge badge-ghost badge-sm">{{ $user->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-sm text-base-content/50">{{ $task->archived_at->format('d M Y, H:i') }}</td>
                            <td class="text-right space-x-1">
                                <button wire:click="openView({{ $task->id }})" class="btn btn-ghost btn-xs">View</button>
                                <button wire:click="confirmDelete({{ $task->id }})" class="btn btn-ghost btn-xs text-error">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($tasks->hasPages())
                <div class="px-4 py-3 border-t border-base-200">{{ $tasks->links() }}</div>
            @endif
        </div>
    @endif

    {{-- View Modal --}}
    @if($showViewModal && $viewingTask)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <div class="flex items-start justify-between gap-4 mb-4">
                <h3 class="font-bold text-lg">{{ $viewingTask->title }}</h3>
                <button type="button" wire:click="closeViewModal" class="btn btn-sm btn-circle btn-ghost">✕</button>
            </div>
            <div class="text-xs text-base-content/40 mb-4">
                Created {{ $viewingTask->created_at->format('d M Y, H:i') }}
            </div>
            @if($viewingTask->address)
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-1">Address</p>
                <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $viewingTask->address }}</p>
            </div>
            @endif
            @if($viewingTask->materials)
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-1">Materials</p>
                <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $viewingTask->materials }}</p>
            </div>
            @endif
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-1">Description</p>
                @if($viewingTask->description)
                    <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $viewingTask->description }}</p>
                @else
                    <p class="text-sm text-base-content/40 italic">No description provided.</p>
                @endif
            </div>
            @if($viewingTask->users->isNotEmpty())
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-2">Assigned to</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($viewingTask->users as $user)
                        <span class="badge badge-ghost badge-sm">{{ $user->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="modal-action">
                <button type="button" wire:click="closeViewModal" class="btn btn-sm btn-primary">Close</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeViewModal"></div>
    </div>
    @endif

    {{-- Delete Modal --}}
    @if($showDeleteModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg">Delete Task</h3>
            <p class="py-4 text-base-content/70">This will permanently delete the task. This action cannot be undone.</p>
            <div class="modal-action">
                <button type="button" wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                <button type="button" wire:click="destroy" class="btn btn-error" wire:loading.attr="disabled">Delete</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif
</div>
