<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public array $selectedEmployees = [];
    public $attachments = [];

    public function save(): void
    {
        $this->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'selectedEmployees'    => 'required|array|min:1',
            'selectedEmployees.*'  => ['integer', Rule::exists('users', 'id')->where('role', 'employee')],
            'attachments'          => 'nullable|array|max:5',
            'attachments.*'        => 'file|mimes:jpg,jpeg,pdf|max:15360',
        ]);

        $task = Task::create([
            'title'       => $this->title,
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
        return [
            'employees' => User::where('role', 'employee')->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Create Task</h2>
        <a href="{{ route('admin.tasks') }}" class="btn btn-ghost btn-sm">← Back</a>
    </div>

    <div class="card bg-base-100 shadow max-w-2xl">
        <div class="card-body space-y-5">
            <form wire:submit="save" class="space-y-5">

                {{-- Title --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Title</span></label>
                    <input wire:model="title" type="text" class="input input-bordered @error('title') input-error @enderror"
                           placeholder="Task title" autofocus />
                    @error('title') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
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
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">Attachments</span>
                        <span class="label-text-alt text-base-content/50">JPG / PDF · max 15 MB each · max 5 files</span>
                    </label>
                    <input wire:model="attachments" type="file" multiple accept=".jpg,.jpeg,.pdf"
                           class="file-input file-input-bordered w-full @error('attachments') file-input-error @enderror" />
                    <div wire:loading wire:target="attachments" class="label">
                        <span class="label-text-alt text-info">
                            <span class="loading loading-spinner loading-xs"></span> Uploading...
                        </span>
                    </div>
                    @error('attachments') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
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
