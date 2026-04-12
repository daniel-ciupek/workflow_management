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

    // View
    public bool $showViewModal = false;
    public ?int $viewingId = null;

    // Delete
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    // Edit
    public bool $showEditModal = false;
    public ?int $editingId = null;
    public string $editTitle = '';
    public string $editAddress = '';
    public string $editMaterials = '';
    public string $editDescription = '';
    public array $editSelectedEmployees = [];
    public array $existingAttachments = [];
    public array $removedAttachments = [];
    public $newAttachments = [];
    public $newAttachmentBatch = [];

    public function addEditBatch(): void
    {
        $this->newAttachments = array_merge($this->newAttachments, $this->newAttachmentBatch);
        $this->newAttachmentBatch = [];
    }

    public function removeNewAttachment(int $index): void
    {
        array_splice($this->newAttachments, $index, 1);
        $this->newAttachments = array_values($this->newAttachments);
    }

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

    public function openEdit(int $id): void
    {
        $task = Task::with('users')->findOrFail($id);
        $this->editingId = $id;
        $this->editTitle = $task->title;
        $this->editAddress = $task->address ?? '';
        $this->editMaterials = $task->materials ?? '';
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
            'editAddress'             => 'nullable|string',
            'editMaterials'           => 'nullable|string',
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
            'address'     => $this->editAddress ?: null,
            'materials'   => $this->editMaterials ?: null,
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
        $this->newAttachmentBatch = [];
        $this->resetValidation();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function destroy(): void
    {
        if (!$this->deletingId) return;

        Task::findOrFail($this->deletingId)->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    public function closeModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $tasks = Task::withCount('users')
            ->with(['users' => fn ($q) => $q->select('users.id', 'users.name')])
            ->active()
            ->where('created_by', auth()->id())
            ->latest()
            ->paginate(10);

        $viewingTask = $this->viewingId
            ? Task::with(['users' => fn ($q) => $q->select('users.id', 'users.name')])->find($this->viewingId)
            : null;

        return [
            'tasks'       => $tasks,
            'viewingTask' => $viewingTask,
            'employees'   => auth()->user()->isSuperAdmin()
                ? User::where('role', 'employee')->orderBy('name')->get()
                : auth()->user()->employees()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="page-enter">
    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tasks</h1>
            <p class="text-slate-500 text-sm mt-0.5">Manage and track your active tasks</p>
        </div>
        <a href="{{ route('admin.tasks.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Task
        </a>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-5 text-sm">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Task list --}}
    <div class="space-y-3">
        @forelse($tasks as $task)
            <div class="task-card bg-white rounded-xl border border-slate-200 p-5 group"
                 style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        {{-- Title --}}
                        <h3 class="font-semibold text-slate-900 text-base leading-snug">{{ $task->title }}</h3>

                        {{-- Description preview --}}
                        @if($task->description)
                            <p class="text-sm text-slate-500 mt-1 line-clamp-2 leading-relaxed">{{ $task->description }}</p>
                        @endif

                        {{-- Meta info --}}
                        <div class="flex flex-wrap items-center gap-3 mt-2.5">
                            <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $task->created_at->format('d M Y') }}
                            </span>
                            @if(!empty($task->attachments))
                                <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                    {{ count($task->attachments) }} file{{ count($task->attachments) !== 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>

                        {{-- Assigned employees --}}
                        @if($task->users->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                @foreach($task->users as $user)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $user->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-0.5 shrink-0">
                        <button wire:click="openView({{ $task->id }})"
                                class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150"
                                title="View details">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button wire:click="openEdit({{ $task->id }})"
                                class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-150"
                                title="Edit task">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="confirmDelete({{ $task->id }})"
                                class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-150"
                                title="Delete task">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-dashed border-slate-300 p-12 text-center">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-slate-500 text-sm font-medium mb-1">No tasks yet</p>
                <p class="text-slate-400 text-xs mb-4">Create your first task to get started</p>
                <a href="{{ route('admin.tasks.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create first task
                </a>
            </div>
        @endforelse
    </div>

    @if($tasks->hasPages())
        <div class="mt-5">{{ $tasks->links() }}</div>
    @endif

    {{-- View Modal --}}
    @if($showViewModal && $viewingTask)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg rounded-2xl p-0 overflow-hidden">
            {{-- Modal header --}}
            <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-slate-100">
                <div>
                    <h3 class="font-semibold text-slate-900 text-lg leading-snug">{{ $viewingTask->title }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Created {{ $viewingTask->created_at->format('d M Y, H:i') }}</p>
                </div>
                <button wire:click="closeViewModal" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="px-6 py-5 space-y-5 max-h-[60vh] overflow-y-auto">
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
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Assigned Employees</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($viewingTask->users as $user)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">{{ $user->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Attachments --}}
                <div x-data="{
                    lightbox: null,
                    scale: 1, tx: 0, ty: 0,
                    dragging: false, ox: 0, oy: 0,
                    open(url) { this.lightbox = url; this.scale = 1; this.tx = 0; this.ty = 0; },
                    close() { this.lightbox = null; },
                    zoom(e) {
                        e.preventDefault();
                        const factor = e.deltaY < 0 ? 1.15 : 0.87;
                        this.scale = Math.max(1, Math.min(12, this.scale * factor));
                        if (this.scale === 1) { this.tx = 0; this.ty = 0; }
                    },
                    grab(e) { if (this.scale > 1) { this.dragging = true; this.ox = e.clientX - this.tx; this.oy = e.clientY - this.ty; } },
                    pan(e) { if (this.dragging) { this.tx = e.clientX - this.ox; this.ty = e.clientY - this.oy; } },
                    drop() { this.dragging = false; },
                    reset() { this.scale = 1; this.tx = 0; this.ty = 0; }
                }">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Attachments</p>
                    @if(!empty($viewingTask->attachments))
                        <div class="flex flex-wrap gap-2">
                            @foreach($viewingTask->attachments as $path)
                                @php $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)); @endphp
                                @if(in_array($ext, ['jpg', 'jpeg']))
                                    @php $url = route('admin.attachments.view', base64_encode($path)); @endphp
                                    <img src="{{ $url }}"
                                         alt="{{ basename($path) }}"
                                         class="w-24 h-24 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity border border-slate-200"
                                         @click="open('{{ $url }}')" />
                                @else
                                    <a href="{{ route('admin.attachments.view', base64_encode($path)) }}"
                                       target="_blank"
                                       class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 px-3 py-2 rounded-lg text-sm text-blue-600 hover:text-blue-700 transition-colors duration-150">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        {{ basename($path) }}
                                    </a>
                                @endif
                            @endforeach
                        </div>

                        {{-- Fullscreen lightbox --}}
                        <div x-show="lightbox"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             @keydown.escape.window="close()"
                             @mouseup.window="drop()"
                             @mousemove.window="pan($event)"
                             class="fixed inset-0 bg-black z-[200] flex flex-col"
                             style="display: none;">
                            <div class="flex items-center justify-between px-4 py-2 bg-black/60 shrink-0 select-none">
                                <div class="flex items-center gap-2">
                                    <button @click="scale = Math.min(12, scale * 1.3)" class="btn btn-sm btn-ghost text-white">+ Zoom in</button>
                                    <button @click="scale = Math.max(1, scale / 1.3); if(scale===1){tx=0;ty=0}" class="btn btn-sm btn-ghost text-white">- Zoom out</button>
                                    <button @click="reset()" class="btn btn-sm btn-ghost text-white">Reset</button>
                                    <span class="text-white/50 text-xs ml-2" x-text="`${Math.round(scale * 100)}%`"></span>
                                </div>
                                <button @click="close()" class="btn btn-sm btn-ghost text-white text-lg leading-none">X</button>
                            </div>
                            <div class="flex-1 overflow-hidden flex items-center justify-center" @click="close()" @wheel.prevent="zoom($event)">
                                <img :src="lightbox"
                                     :style="`transform: translate(${tx}px, ${ty}px) scale(${scale}); cursor: ${dragging ? 'grabbing' : scale > 1 ? 'grab' : 'zoom-in'}; transform-origin: center;`"
                                     class="max-w-full max-h-full object-contain select-none"
                                     @click.stop @mousedown.stop="grab($event)" @dblclick.stop="scale === 1 ? (scale = 2) : reset()" draggable="false" />
                            </div>
                            <div class="text-center text-white/30 text-xs py-2 shrink-0 select-none">
                                Scroll to zoom · Drag to pan · Double-click to zoom · ESC to close
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-slate-400 italic">No attachments.</p>
                    @endif
                </div>
            </div>

            {{-- Modal footer --}}
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button wire:click="openEdit({{ $viewingTask->id }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </button>
                <button wire:click="closeViewModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-150">
                    Close
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeViewModal"></div>
    </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg rounded-2xl p-0 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h3 class="font-semibold text-slate-900 text-lg">Edit Task</h3>
                <button wire:click="closeEditModal" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 max-h-[65vh] overflow-y-auto">
            <form wire:submit="saveEdit" class="space-y-4" id="editForm">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Project</label>
                    <input wire:model="editTitle" type="text"
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('editTitle') border-red-400 bg-red-50 @enderror"
                           placeholder="Task title" autofocus />
                    @error('editTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                    <textarea wire:model="editAddress" rows="2"
                              class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none resize-none @error('editAddress') border-red-400 bg-red-50 @enderror"
                              placeholder="Site address..."></textarea>
                    @error('editAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Materials</label>
                    <textarea wire:model="editMaterials" rows="2"
                              class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none resize-none @error('editMaterials') border-red-400 bg-red-50 @enderror"
                              placeholder="Required materials..."></textarea>
                    @error('editMaterials') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea wire:model="editDescription" rows="2"
                              class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none resize-none @error('editDescription') border-red-400 bg-red-50 @enderror"
                              placeholder="Optional description..."></textarea>
                    @error('editDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-slate-700">Assign to Employees</label>
                        <span class="text-xs text-slate-400">{{ count($editSelectedEmployees) }} selected</span>
                    </div>
                    <div class="border border-slate-300 rounded-lg p-2 max-h-36 overflow-y-auto space-y-0.5">
                        @forelse($employees as $employee)
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 px-2 py-1.5 rounded-md">
                                <input type="checkbox" wire:model="editSelectedEmployees" value="{{ $employee->id }}"
                                       class="checkbox checkbox-primary checkbox-sm" />
                                <span class="text-sm text-slate-700">{{ $employee->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-400 text-center py-2">No employees found.</p>
                        @endforelse
                    </div>
                    @error('editSelectedEmployees') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @if(!empty($existingAttachments))
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Current Attachments</label>
                        <ul class="space-y-1">
                            @foreach($existingAttachments as $path)
                                <li class="flex items-center justify-between gap-2 text-sm bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg">
                                    <span class="truncate text-slate-600 font-mono text-xs">{{ basename($path) }}</span>
                                    <button type="button" wire:click="removeExistingAttachment('{{ $path }}')"
                                            class="text-red-500 hover:text-red-700 shrink-0 text-xs font-medium">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div x-data="{
                    uploading: false,
                    progress: 0,
                    handle(e) {
                        const selected = Array.from(e.target.files);
                        if (!selected.length) return;
                        this.uploading = true;
                        this.progress = 0;
                        $wire.uploadMultiple('newAttachmentBatch', selected,
                            () => { this.uploading = false; $wire.call('addEditBatch'); e.target.value = ''; },
                            () => { this.uploading = false; },
                            (pct) => { this.progress = isFinite(pct) ? pct : 0; }
                        );
                    }
                }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-slate-700">Add Attachments</label>
                        <span class="text-xs text-slate-400">JPG / PDF · max 15 MB</span>
                    </div>
                    <input type="file" multiple accept=".jpg,.jpeg,.pdf"
                           @change="handle($event)"
                           class="block w-full text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-lg cursor-pointer @error('newAttachments') border-red-400 @enderror" />

                    <div x-show="uploading" class="mt-2">
                        <div class="flex items-center gap-2 text-xs text-blue-600">
                            <span class="loading loading-spinner loading-xs"></span>
                            Uploading... <span x-text="progress + '%'"></span>
                        </div>
                        <progress class="progress progress-info w-full mt-1" :value="progress" max="100"></progress>
                    </div>

                    @if(count($newAttachments) > 0)
                        <ul class="mt-2 space-y-1">
                            @foreach($newAttachments as $i => $file)
                                <li class="flex items-center justify-between gap-2 text-xs bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg">
                                    <span class="text-slate-600 truncate">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeNewAttachment({{ $i }})"
                                            class="text-red-500 hover:text-red-700 shrink-0 font-medium">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @error('newAttachments.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </form>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" wire:click="closeEditModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors duration-150">
                    Cancel
                </button>
                <button type="submit" form="editForm"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-150 disabled:opacity-60"
                        wire:loading.attr="disabled" wire:target="saveEdit">
                    <span wire:loading.remove wire:target="saveEdit">Save Changes</span>
                    <span wire:loading wire:target="saveEdit" class="flex items-center gap-1.5">
                        <span class="loading loading-spinner loading-sm"></span>
                    </span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeEditModal"></div>
    </div>
    @endif

    {{-- Confirm Delete --}}
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
                <p class="text-sm text-slate-600">This will permanently delete the task and all its attachments. Employees will lose access to it.</p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" wire:click="closeModal"
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
        <div class="modal-backdrop" wire:click="closeModal"></div>
    </div>
    @endif
</div>
