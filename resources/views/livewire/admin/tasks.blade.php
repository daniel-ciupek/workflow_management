<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Delete
    public bool $confirmDelete = false;
    public ?int $deletingId = null;

    // Edit
    public bool $showEditModal = false;
    public ?int $editingId = null;
    public string $editTitle = '';
    public string $editDescription = '';
    public array $editSelectedEmployees = [];
    public array $existingAttachments = [];
    public array $removedAttachments = [];
    public $newAttachments = [];

    public function openEdit(int $id): void
    {
        $task = Task::with('users')->findOrFail($id);
        $this->editingId = $id;
        $this->editTitle = $task->title;
        $this->editDescription = $task->description ?? '';
        $this->editSelectedEmployees = $task->users->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->existingAttachments = $task->attachments ?? [];
        $this->removedAttachments = [];
        $this->newAttachments = [];
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function removeExistingAttachment(string $path): void
    {
        $this->removedAttachments[] = $path;
        $this->existingAttachments = array_values(
            array_filter($this->existingAttachments, fn($p) => $p !== $path)
        );
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editTitle'               => 'required|string|max:255',
            'editDescription'         => 'nullable|string',
            'editSelectedEmployees'   => 'required|array|min:1',
            'editSelectedEmployees.*' => ['integer', Rule::exists('users', 'id')->where('role', 'employee')],
            'newAttachments'          => 'nullable|array',
            'newAttachments.*'        => 'file|mimes:jpg,jpeg,pdf|max:15360',
        ]);

        $task = Task::findOrFail($this->editingId);

        // Delete removed attachments from disk
        foreach ($this->removedAttachments as $path) {
            Storage::disk('tasks')->delete($path);
        }

        // Upload new attachments
        $paths = $this->existingAttachments;
        foreach ($this->newAttachments as $file) {
            $paths[] = $file->store("task-{$task->id}", 'tasks');
        }

        $task->update([
            'title'       => $this->editTitle,
            'description' => $this->editDescription,
            'attachments' => empty($paths) ? null : array_values($paths),
        ]);

        $task->users()->sync($this->editSelectedEmployees);

        $this->showEditModal = false;
        $this->editingId = null;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingId = null;
        $this->newAttachments = [];
        $this->resetValidation();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->confirmDelete = true;
    }

    public function destroy(): void
    {
        if (!$this->deletingId) return;

        Task::findOrFail($this->deletingId)->delete();

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
            'employees' => User::where('role', 'employee')->orderBy('name')->get(),
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

                        <div class="flex gap-1 shrink-0">
                            <button wire:click="openEdit({{ $task->id }})" class="btn btn-ghost btn-xs">Edit</button>
                            <button wire:click="confirmDelete({{ $task->id }})" class="btn btn-ghost btn-xs text-error">Delete</button>
                        </div>
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

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="modal modal-open">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg mb-4">Edit Task</h3>
            <form wire:submit="saveEdit" class="space-y-4">

                {{-- Title --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Title</span></label>
                    <input wire:model="editTitle" type="text"
                           class="input input-bordered @error('editTitle') input-error @enderror"
                           placeholder="Task title" autofocus />
                    @error('editTitle') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Description --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Description</span></label>
                    <textarea wire:model="editDescription" rows="3"
                              class="textarea textarea-bordered @error('editDescription') textarea-error @enderror"
                              placeholder="Optional description..."></textarea>
                    @error('editDescription') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Employees --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Assign to Employees</span>
                        <span class="label-text-alt text-base-content/50">{{ count($editSelectedEmployees) }} selected</span>
                    </label>
                    <div class="border border-base-300 rounded-box p-3 max-h-40 overflow-y-auto space-y-1">
                        @forelse($employees as $employee)
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-base-200 px-2 py-1.5 rounded-lg">
                                <input type="checkbox" wire:model="editSelectedEmployees" value="{{ $employee->id }}"
                                       class="checkbox checkbox-primary checkbox-sm" />
                                <span class="text-sm">{{ $employee->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-base-content/50 text-center py-2">No employees found.</p>
                        @endforelse
                    </div>
                    @error('editSelectedEmployees') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Existing Attachments --}}
                @if(!empty($existingAttachments))
                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">Current Attachments</span></label>
                        <ul class="space-y-1">
                            @foreach($existingAttachments as $path)
                                <li class="flex items-center justify-between gap-2 text-sm bg-base-200 px-3 py-1.5 rounded-lg">
                                    <span class="truncate text-base-content/70 font-mono">{{ basename($path) }}</span>
                                    <button type="button" wire:click="removeExistingAttachment('{{ $path }}')"
                                            class="text-error hover:text-error shrink-0 text-xs">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- New Attachments --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Add Attachments</span>
                        <span class="label-text-alt text-base-content/50">JPG / PDF · max 15 MB</span>
                    </label>
                    <input wire:model="newAttachments" type="file" multiple accept=".jpg,.jpeg,.pdf"
                           class="file-input file-input-bordered file-input-sm w-full @error('newAttachments') file-input-error @enderror" />
                    <div wire:loading wire:target="newAttachments" class="label">
                        <span class="label-text-alt text-info">
                            <span class="loading loading-spinner loading-xs"></span> Uploading...
                        </span>
                    </div>
                    @error('newAttachments.*') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="closeEditModal" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveEdit">
                        <span wire:loading.remove wire:target="saveEdit">Save Changes</span>
                        <span wire:loading wire:target="saveEdit"><span class="loading loading-spinner loading-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeEditModal"></div>
    </div>
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
