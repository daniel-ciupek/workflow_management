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
                            <h3 class="font-semibold text-lg">{{ $task->title }}</h3>

                            @if($task->description)
                                <p class="text-sm text-base-content/60 mt-1 line-clamp-2">{{ $task->description }}</p>
                            @endif

                            <div class="text-xs text-base-content/40 mt-2">
                                Created {{ $task->created_at->format('d M Y') }}
                                @if(!empty($task->attachments))
                                    &bull; {{ count($task->attachments) }} attachment(s)
                                @endif
                            </div>

                            @if($task->users->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($task->users as $user)
                                        <span class="badge badge-ghost badge-sm">{{ $user->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-1 shrink-0">
                            <button wire:click="openView({{ $task->id }})" class="btn btn-ghost btn-xs">View</button>
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

    {{-- View Modal --}}
    @if($showViewModal && $viewingTask)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <div class="flex items-start justify-between gap-4 mb-4">
                <h3 class="font-bold text-lg">{{ $viewingTask->title }}</h3>
                <button wire:click="closeViewModal" class="btn btn-sm btn-circle btn-ghost">✕</button>
            </div>

            <div class="text-xs text-base-content/40 mb-4">
                Created {{ $viewingTask->created_at->format('d M Y, H:i') }}
            </div>

            {{-- Address --}}
            @if($viewingTask->address)
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-1">Address</p>
                <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $viewingTask->address }}</p>
            </div>
            @endif

            {{-- Materials --}}
            @if($viewingTask->materials)
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-1">Materials</p>
                <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $viewingTask->materials }}</p>
            </div>
            @endif

            {{-- Description --}}
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-1">Description</p>
                @if($viewingTask->description)
                    <p class="text-sm text-base-content/80 whitespace-pre-wrap">{{ $viewingTask->description }}</p>
                @else
                    <p class="text-sm text-base-content/40 italic">No description provided.</p>
                @endif
            </div>

            {{-- Employees --}}
            @if($viewingTask->users->isNotEmpty())
            <div class="mb-4">
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-2">Assigned Employees</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($viewingTask->users as $user)
                        <span class="badge badge-ghost badge-sm">{{ $user->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Attachments --}}
            <div class="mb-4" x-data="{
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
                <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-2">Attachments</p>
                @if(!empty($viewingTask->attachments))
                    <div class="flex flex-wrap gap-2">
                        @foreach($viewingTask->attachments as $path)
                            @php $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)); @endphp
                            @if(in_array($ext, ['jpg', 'jpeg']))
                                @php $url = route('admin.attachments.view', base64_encode($path)); @endphp
                                <img src="{{ $url }}"
                                     alt="{{ basename($path) }}"
                                     class="w-24 h-24 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity border border-base-300"
                                     @click="open('{{ $url }}')" />
                            @else
                                <a href="{{ route('admin.attachments.view', base64_encode($path)) }}"
                                   target="_blank"
                                   class="flex items-center gap-2 bg-base-200 px-3 py-2 rounded-lg text-sm link link-primary hover:bg-base-300">
                                    📄 {{ basename($path) }}
                                </a>
                            @endif
                        @endforeach
                    </div>

                    {{-- Fullscreen lightbox with zoom & pan --}}
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

                        {{-- Toolbar --}}
                        <div class="flex items-center justify-between px-4 py-2 bg-black/60 shrink-0 select-none">
                            <div class="flex items-center gap-2">
                                <button @click="scale = Math.min(12, scale * 1.3)" class="btn btn-sm btn-ghost text-white">＋ Zoom in</button>
                                <button @click="scale = Math.max(1, scale / 1.3); if(scale===1){tx=0;ty=0}" class="btn btn-sm btn-ghost text-white">－ Zoom out</button>
                                <button @click="reset()" class="btn btn-sm btn-ghost text-white">⟳ Reset</button>
                                <span class="text-white/50 text-xs ml-2" x-text="`${Math.round(scale * 100)}%`"></span>
                            </div>
                            <button @click="close()" class="btn btn-sm btn-ghost text-white text-lg leading-none">✕</button>
                        </div>

                        {{-- Image area --}}
                        <div class="flex-1 overflow-hidden flex items-center justify-center"
                             @click="close()"
                             @wheel.prevent="zoom($event)">
                            <img :src="lightbox"
                                 :style="`transform: translate(${tx}px, ${ty}px) scale(${scale}); cursor: ${dragging ? 'grabbing' : scale > 1 ? 'grab' : 'zoom-in'}; transform-origin: center;`"
                                 class="max-w-full max-h-full object-contain select-none"
                                 @click.stop
                                 @mousedown.stop="grab($event)"
                                 @dblclick.stop="scale === 1 ? (scale = 2) : reset()"
                                 draggable="false" />
                        </div>

                        {{-- Hint --}}
                        <div class="text-center text-white/30 text-xs py-2 shrink-0 select-none">
                            Scroll to zoom · Drag to pan · Double-click to zoom · ESC to close
                        </div>
                    </div>
                @else
                    <p class="text-sm text-base-content/40 italic">No attachments.</p>
                @endif
            </div>

            <div class="modal-action">
                <button wire:click="openEdit({{ $viewingTask->id }})" class="btn btn-sm btn-outline">Edit</button>
                <button wire:click="closeViewModal" class="btn btn-sm btn-primary">Close</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeViewModal"></div>
    </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg mb-4">Edit Task</h3>
            <form wire:submit="saveEdit" class="space-y-4">

                {{-- Project --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Project</span></label>
                    <input wire:model="editTitle" type="text"
                           class="input input-bordered @error('editTitle') input-error @enderror"
                           placeholder="Task title" autofocus />
                    @error('editTitle') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Address --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Address</span></label>
                    <textarea wire:model="editAddress" rows="3"
                              class="textarea textarea-bordered @error('editAddress') textarea-error @enderror"
                              placeholder="Site address..."></textarea>
                    @error('editAddress') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Materials --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Materials</span></label>
                    <textarea wire:model="editMaterials" rows="3"
                              class="textarea textarea-bordered @error('editMaterials') textarea-error @enderror"
                              placeholder="Required materials..."></textarea>
                    @error('editMaterials') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
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
                <div class="form-control"
                     x-data="{
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
                    <label class="label">
                        <span class="label-text font-medium">Add Attachments</span>
                        <span class="label-text-alt text-base-content/50">JPG / PDF · max 15 MB · multiple batches allowed</span>
                    </label>
                    <input type="file" multiple accept=".jpg,.jpeg,.pdf"
                           @change="handle($event)"
                           class="file-input file-input-bordered file-input-sm w-full @error('newAttachments') file-input-error @enderror" />

                    <div x-show="uploading" class="mt-2">
                        <div class="flex items-center gap-2 text-sm text-info">
                            <span class="loading loading-spinner loading-xs"></span>
                            Uploading... <span x-text="progress + '%'"></span>
                        </div>
                        <progress class="progress progress-info w-full mt-1" :value="progress" max="100"></progress>
                    </div>

                    @if(count($newAttachments) > 0)
                        <ul class="mt-2 space-y-1">
                            @foreach($newAttachments as $i => $file)
                                <li class="flex items-center justify-between gap-2 text-xs bg-base-200 px-3 py-1.5 rounded-lg">
                                    <span class="text-base-content/70 truncate">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeNewAttachment({{ $i }})"
                                            class="text-error shrink-0 hover:underline">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

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
    @if($showDeleteModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg">Delete Task</h3>
            <p class="py-4 text-base-content/70">This will permanently delete the task and all its attachments. Employees will lose access to it.</p>
            <div class="modal-action">
                <button type="button" wire:click="closeModal" class="btn btn-ghost">Cancel</button>
                <button type="button" wire:click="destroy" class="btn btn-error" wire:loading.attr="disabled">Delete</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModal"></div>
    </div>
    @endif
</div>
