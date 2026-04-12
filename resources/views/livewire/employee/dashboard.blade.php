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
        $employeeId = session('employee_id');
        if (!$employeeId) {
            $this->redirect(route('employee.select'));
            return ['tasks' => collect(), 'viewingTask' => null];
        }

        $tasks = Task::with([
                'users'   => fn ($q) => $q->select('users.id', 'users.name'),
                'creator' => fn ($q) => $q->select('users.id', 'users.name'),
            ])
            ->active()
            ->whereHas('users', fn ($q) => $q->where('users.id', $employeeId))
            ->select('tasks.id', 'tasks.title', 'tasks.created_at', 'tasks.created_by')
            ->orderBy('tasks.created_at', 'desc')
            ->get();

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
            <h1 class="text-2xl font-bold text-slate-900">My Tasks</h1>
            <p class="text-slate-500 text-sm mt-0.5">Tap a task to view details</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-slate-100 text-slate-600">
            {{ $tasks->count() }} active
        </span>
    </div>

    @if($tasks->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-12 text-center">
            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-slate-500 text-sm font-medium">No tasks available</p>
            <p class="text-slate-400 text-xs mt-1">Check back later for new assignments</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($tasks as $task)
                <button wire:click="openView({{ $task->id }})"
                        class="text-left bg-white rounded-xl border border-slate-200 p-5 transition-all duration-200 active:scale-[0.98] w-full"
                        style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06);">
                    {{-- Status indicator --}}
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <h3 class="font-semibold text-slate-900 text-base leading-snug">{{ $task->title }}</h3>
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 shrink-0"></div>
                    </div>

                    <div class="space-y-1.5">
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ $task->creator?->name ?? '—' }}
                        </p>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ $task->created_at->format('d M Y') }}
                        </p>
                    </div>

                    @if($task->users->isNotEmpty())
                        <div class="flex flex-wrap gap-1 mt-3">
                            @foreach($task->users as $user)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">
                                    {{ $user->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <p class="text-xs text-blue-600 font-medium mt-3 flex items-center gap-1">
                        View details
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </p>
                </button>
            @endforeach
        </div>
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
                                    @php $url = route('employee.attachments.view', base64_encode($path)); @endphp
                                    <img src="{{ $url }}"
                                         alt="{{ basename($path) }}"
                                         class="w-24 h-24 object-cover rounded-lg cursor-pointer transition-opacity border border-slate-200"
                                         @click="open('{{ $url }}')" />
                                @else
                                    <a href="{{ route('employee.attachments.view', base64_encode($path)) }}"
                                       target="_blank"
                                       class="flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-2 rounded-lg text-sm text-blue-600 hover:text-blue-700 transition-colors duration-150">
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

            {{-- Modal footer --}}
            <div class="flex items-center justify-end px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button wire:click="closeViewModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-150">
                    Close
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeViewModal"></div>
    </div>
    @endif
</div>
