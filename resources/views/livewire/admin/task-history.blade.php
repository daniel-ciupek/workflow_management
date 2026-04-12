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
    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Task History</h1>
            <p class="text-slate-500 text-sm mt-0.5">Archived tasks older than 24 hours</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-slate-100 text-slate-600">
            {{ $tasks->total() }} archived
        </span>
    </div>

    @if($tasks->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-12 text-center">
            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <p class="text-slate-500 text-sm font-medium">No archived tasks yet</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Task</th>
                            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Assigned to</th>
                            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Archived</th>
                            <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($tasks as $task)
                            <tr class="hover:bg-slate-50 transition-colors duration-100">
                                <td class="px-5 py-3.5">
                                    <span class="text-sm font-medium text-slate-700">{{ $task->title }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($task->users as $user)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">{{ $user->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-xs text-slate-400">{{ $task->archived_at->format('d M Y, H:i') }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="openView({{ $task->id }})"
                                                class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-md transition-colors duration-150"
                                                title="View">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $task->id }})"
                                                class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors duration-150"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($tasks->hasPages())
                <div class="px-5 py-3 border-t border-slate-100">{{ $tasks->links() }}</div>
            @endif
        </div>
    @endif

    {{-- View Modal --}}
    @if($showViewModal && $viewingTask)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg rounded-2xl p-0 overflow-hidden">
            <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-slate-100">
                <div>
                    <h3 class="font-semibold text-slate-900 text-lg leading-snug">{{ $viewingTask->title }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Created {{ $viewingTask->created_at->format('d M Y, H:i') }}</p>
                </div>
                <button type="button" wire:click="closeViewModal" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">
                @if($viewingTask->address)
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Address</p>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap bg-slate-50 rounded-lg px-3 py-2.5">{{ $viewingTask->address }}</p>
                </div>
                @endif
                @if($viewingTask->materials)
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Materials</p>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap bg-slate-50 rounded-lg px-3 py-2.5">{{ $viewingTask->materials }}</p>
                </div>
                @endif
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Description</p>
                    @if($viewingTask->description)
                        <p class="text-sm text-slate-700 whitespace-pre-wrap bg-slate-50 rounded-lg px-3 py-2.5">{{ $viewingTask->description }}</p>
                    @else
                        <p class="text-sm text-slate-400 italic">No description provided.</p>
                    @endif
                </div>
                @if($viewingTask->users->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Assigned to</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($viewingTask->users as $user)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">{{ $user->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div class="flex items-center justify-end px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" wire:click="closeViewModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-150">
                    Close
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeViewModal"></div>
    </div>
    @endif

    {{-- Delete Modal --}}
    @if($showDeleteModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-sm rounded-2xl p-0 overflow-hidden">
            <div class="px-6 py-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-900">Delete Task</h3>
                </div>
                <p class="text-sm text-slate-600">This will permanently delete the task. This action cannot be undone.</p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" wire:click="closeModals"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors duration-150">
                    Cancel
                </button>
                <button type="button" wire:click="destroy"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors duration-150 disabled:opacity-60"
                        wire:loading.attr="disabled">
                    Delete
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif
</div>
