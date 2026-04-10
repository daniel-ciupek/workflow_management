<?php

use App\Models\Task;
use Livewire\Volt\Component;

new class extends Component {

    public bool $showViewModal = false;
    public ?int $viewingId = null;

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

    public function markDone(int $taskId): void
    {
        $employeeId = session('employee_id');
        if (!$employeeId) return;

        \App\Models\Task::findOrFail($taskId)
            ->users()
            ->updateExistingPivot($employeeId, [
                'done'         => true,
                'completed_at' => now(),
            ]);

        if ($this->viewingId === $taskId) {
            $this->viewingId = $taskId; // force refresh
        }
    }

    public function with(): array
    {
        $tasks = Task::with(['users' => fn ($q) => $q->select('users.id', 'users.name')])
            ->select('tasks.id', 'tasks.title', 'tasks.created_at')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

        $viewingTask = $this->viewingId
            ? Task::with(['users' => fn ($q) => $q->withPivot('done', 'completed_at')->select('users.id', 'users.name')])->find($this->viewingId)
            : null;

        return compact('tasks', 'viewingTask');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Tasks</h2>
        <span class="badge badge-ghost">{{ $tasks->count() }} total</span>
    </div>

    @if($tasks->isEmpty())
        <div class="card bg-base-100 shadow text-center text-base-content/50 py-12">
            No tasks available.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($tasks as $task)
                <div class="card bg-base-100 shadow cursor-pointer hover:shadow-md transition-shadow p-5"
                     wire:click="openView({{ $task->id }})">
                    <h3 class="font-semibold text-base">{{ $task->title }}</h3>
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
                    <p class="text-xs text-primary mt-2">Tap to view details →</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- View Modal --}}
    @if($showViewModal && $viewingTask)
    <div class="modal modal-open">
        <div class="modal-box max-w-lg">
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
                                @php $url = route('employee.attachments.view', base64_encode($path)); @endphp
                                <img src="{{ $url }}"
                                     alt="{{ basename($path) }}"
                                     class="w-24 h-24 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity border border-base-300"
                                     @click="open('{{ $url }}')" />
                            @else
                                <a href="{{ route('employee.attachments.view', base64_encode($path)) }}"
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

            {{-- Assigned employees --}}
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
                <button wire:click="closeViewModal" class="btn btn-sm btn-primary">Close</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeViewModal"></div>
    </div>
    @endif
</div>
