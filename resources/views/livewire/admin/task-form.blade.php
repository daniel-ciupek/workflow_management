<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public string $title = '';
    public string $address = '';
    public string $materials = '';
    public string $description = '';
    public array $selectedEmployees = [];
    public $attachments = [];
    public $attachmentBatch = [];

    public function addBatch(): void
    {
        $this->attachments = array_merge($this->attachments, $this->attachmentBatch);
        $this->attachmentBatch = [];
    }

    public function removeAttachment(int $index): void
    {
        array_splice($this->attachments, $index, 1);
        $this->attachments = array_values($this->attachments);
    }

    public function save(): void
    {
        $this->validate([
            'title'                => 'required|string|max:255',
            'address'              => 'nullable|string|max:255',
            'materials'            => 'nullable|string',
            'description'          => 'nullable|string',
            'selectedEmployees'    => 'required|array|min:1',
            'selectedEmployees.*'  => ['integer', Rule::exists('users', 'id')->where('role', 'employee')],
            'attachments'          => 'nullable|array',
            'attachments.*'        => 'file|mimes:jpg,jpeg,pdf|max:15360',
        ]);

        $task = Task::create([
            'created_by'  => auth()->id(),
            'title'       => $this->title,
            'address'     => $this->address ?: null,
            'materials'   => $this->materials ?: null,
            'description' => $this->description,
        ]);

        // Store attachments
        $paths = [];
        foreach ($this->attachments as $file) {
            $paths[] = $file->store("task-{$task->id}", 'tasks');
        }

        if (!empty($paths)) {
            $task->update(['attachments' => $paths]);
        }

        // Attach employees
        $task->users()->attach($this->selectedEmployees);

        session()->flash('success', 'Task created successfully.');
        $this->redirect(route('admin.tasks'), navigate: true);
    }

    public function with(): array
    {
        $employees = auth()->user()->isSuperAdmin()
            ? User::where('role', 'employee')->orderBy('name')->get()
            : auth()->user()->employees()->orderBy('name')->get();

        return compact('employees');
    }
}; ?>

<div class="max-w-2xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.tasks') }}"
           class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Create Task</h1>
            <p class="text-slate-500 text-sm mt-0.5">Add a new task and assign it to employees</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06);">
        <div class="px-6 py-6">
            <form wire:submit="save" class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Project <span class="text-red-500">*</span></label>
                    <input wire:model="title" type="text"
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('title') border-red-400 bg-red-50 @enderror"
                           placeholder="Project name" autofocus />
                    @error('title') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                    <textarea wire:model="address"
                              class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none resize-none @error('address') border-red-400 bg-red-50 @enderror"
                              rows="3" placeholder="Site address..."></textarea>
                    @error('address') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Materials</label>
                    <textarea wire:model="materials"
                              class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none resize-none @error('materials') border-red-400 bg-red-50 @enderror"
                              rows="3" placeholder="Required materials..."></textarea>
                    @error('materials') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                    <textarea wire:model="description"
                              class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none resize-none @error('description') border-red-400 bg-red-50 @enderror"
                              rows="3" placeholder="Optional description..."></textarea>
                    @error('description') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Employees --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-slate-700">Assign to Employees <span class="text-red-500">*</span></label>
                        <span class="text-xs text-slate-400">{{ count($selectedEmployees) }} selected</span>
                    </div>
                    <div class="border border-slate-300 rounded-lg p-2 max-h-48 overflow-y-auto space-y-0.5">
                        @forelse($employees as $employee)
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 px-2 py-1.5 rounded-md">
                                <input type="checkbox" wire:model="selectedEmployees" value="{{ $employee->id }}"
                                       class="checkbox checkbox-primary checkbox-sm" />
                                <span class="text-sm text-slate-700">{{ $employee->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-400 text-center py-3">No employees found. Add employees first.</p>
                        @endforelse
                    </div>
                    @error('selectedEmployees') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Attachments --}}
                <div x-data="{
                    uploading: false,
                    progress: 0,
                    handle(e) {
                        const selected = Array.from(e.target.files);
                        if (!selected.length) return;
                        this.uploading = true;
                        this.progress = 0;
                        $wire.uploadMultiple('attachmentBatch', selected,
                            () => { this.uploading = false; $wire.call('addBatch'); e.target.value = ''; },
                            () => { this.uploading = false; },
                            (pct) => { this.progress = isFinite(pct) ? pct : 0; }
                        );
                    }
                }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-slate-700">Attachments</label>
                        <span class="text-xs text-slate-400">JPG / PDF · max 15 MB · multiple batches allowed</span>
                    </div>
                    <input type="file" multiple accept=".jpg,.jpeg,.pdf"
                           @change="handle($event)"
                           class="block w-full text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-lg cursor-pointer @error('attachments') border-red-400 @enderror" />

                    <div x-show="uploading" class="mt-2">
                        <div class="flex items-center gap-2 text-xs text-blue-600">
                            <span class="loading loading-spinner loading-xs"></span>
                            Uploading... <span x-text="progress + '%'"></span>
                        </div>
                        <progress class="progress progress-info w-full mt-1" :value="progress" max="100"></progress>
                    </div>

                    @if(count($attachments) > 0)
                        <ul class="mt-2 space-y-1">
                            @foreach($attachments as $i => $file)
                                <li class="flex items-center justify-between gap-2 text-xs bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg">
                                    <span class="text-slate-600 truncate">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeAttachment({{ $i }})"
                                            class="text-red-500 hover:text-red-700 shrink-0 font-medium">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @error('attachments.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <div class="pt-2 border-t border-slate-100">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60"
                            wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Create Task</span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                            <span class="loading loading-spinner loading-sm"></span>
                            Saving...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
