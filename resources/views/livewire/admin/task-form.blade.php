<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

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

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Create Task</h2>
        <a href="{{ route('admin.tasks') }}" class="btn btn-ghost btn-sm">← Back</a>
    </div>

    <div class="card bg-base-100 shadow w-full max-w-2xl">
        <div class="card-body space-y-5">
            <form wire:submit="save" class="space-y-5">

                {{-- Project --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Project</span></label>
                    <input wire:model="title" type="text" class="input input-bordered @error('title') input-error @enderror"
                           placeholder="Project name" autofocus />
                    @error('title') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Address --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Address</span></label>
                    <textarea wire:model="address" class="textarea textarea-bordered @error('address') textarea-error @enderror"
                              rows="4" placeholder="Site address..."></textarea>
                    @error('address') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Materials --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Materials</span></label>
                    <textarea wire:model="materials" class="textarea textarea-bordered @error('materials') textarea-error @enderror"
                              rows="4" placeholder="Required materials..."></textarea>
                    @error('materials') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Description --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Description</span></label>
                    <textarea wire:model="description" class="textarea textarea-bordered @error('description') textarea-error @enderror"
                              rows="4" placeholder="Optional description..."></textarea>
                    @error('description') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Employees --}}
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Assign to Employees</span>
                        <span class="label-text-alt text-base-content/50">{{ count($selectedEmployees) }} selected</span>
                    </label>
                    <div class="border border-base-300 rounded-box p-3 max-h-48 overflow-y-auto space-y-1">
                        @forelse($employees as $employee)
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-base-200 px-2 py-1.5 rounded-lg">
                                <input type="checkbox" wire:model="selectedEmployees" value="{{ $employee->id }}"
                                       class="checkbox checkbox-primary checkbox-sm" />
                                <span class="text-sm">{{ $employee->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-base-content/50 text-center py-2">No employees found. Add employees first.</p>
                        @endforelse
                    </div>
                    @error('selectedEmployees') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Attachments --}}
                <div class="form-control"
                     x-data="{
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
                                 (pct) => { this.progress = pct; }
                             );
                         }
                     }">
                    <label class="label">
                        <span class="label-text font-medium">Attachments</span>
                        <span class="label-text-alt text-base-content/50">JPG / PDF · max 15 MB · można dodawać partiami</span>
                    </label>
                    <input type="file" multiple accept=".jpg,.jpeg,.pdf"
                           @change="handle($event)"
                           class="file-input file-input-bordered w-full @error('attachments') file-input-error @enderror" />

                    <div x-show="uploading" class="mt-2">
                        <div class="flex items-center gap-2 text-sm text-info">
                            <span class="loading loading-spinner loading-xs"></span>
                            Uploading... <span x-text="progress + '%'"></span>
                        </div>
                        <progress class="progress progress-info w-full mt-1" :value="progress" max="100"></progress>
                    </div>

                    {{-- Accumulated files list --}}
                    @if(count($attachments) > 0)
                        <ul class="mt-2 space-y-1">
                            @foreach($attachments as $i => $file)
                                <li class="flex items-center justify-between gap-2 text-xs bg-base-200 px-3 py-1.5 rounded-lg">
                                    <span class="text-base-content/70 truncate">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeAttachment({{ $i }})"
                                            class="text-error shrink-0 hover:underline">Remove</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @error('attachments.*') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Create Task</span>
                        <span wire:loading wire:target="save"><span class="loading loading-spinner loading-sm"></span> Saving...</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
