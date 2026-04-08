<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public bool $showModal = false;
    public bool $confirmDelete = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $name = '';
    public string $pin = '';

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->pin = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'pin'  => $this->editingId
                ? 'nullable|digits:4|unique:users,pin,' . $this->editingId
                : 'required|digits:4|unique:users,pin',
        ]);

        if ($this->editingId) {
            $data = ['name' => $this->name];
            if ($this->pin !== '') {
                $data['pin'] = $this->pin;
            }
            User::findOrFail($this->editingId)->update($data);
        } else {
            User::create([
                'name' => $this->name,
                'pin'  => $this->pin,
                'role' => 'employee',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->confirmDelete = true;
    }

    public function destroy(): void
    {
        if ($this->deletingId) {
            User::findOrFail($this->deletingId)->delete();
        }
        $this->confirmDelete = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    public function closeModals(): void
    {
        $this->showModal = false;
        $this->confirmDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->pin = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'employees' => User::where('role', 'employee')
                ->orderBy('name')
                ->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Employees</h2>
        <button wire:click="openCreate" class="btn btn-primary btn-sm">+ Add Employee</button>
    </div>

    {{-- Table --}}
    <div class="card bg-base-100 shadow overflow-x-auto">
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>PIN</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td class="text-base-content/50 text-sm">{{ $employee->id }}</td>
                        <td class="font-medium">{{ $employee->name }}</td>
                        <td><span class="badge badge-ghost font-mono">****</span></td>
                        <td class="text-right space-x-1">
                            <button wire:click="openEdit({{ $employee->id }})" class="btn btn-ghost btn-xs">Edit</button>
                            <button wire:click="confirmDelete({{ $employee->id }})" class="btn btn-ghost btn-xs text-error">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-base-content/50 py-8">No employees yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($employees->hasPages())
            <div class="px-4 py-3 border-t border-base-200">{{ $employees->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if($showModal)
    <div class="modal modal-open">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">{{ $editingId ? 'Edit Employee' : 'New Employee' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Name</span></label>
                    <input wire:model="name" type="text" class="input input-bordered @error('name') input-error @enderror" placeholder="Full name" autofocus />
                    @error('name') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">PIN (4 digits)</span>
                        @if($editingId) <span class="label-text-alt text-base-content/50">Leave blank to keep current</span> @endif
                    </label>
                    <input wire:model="pin" type="password" inputmode="numeric" maxlength="4"
                           class="input input-bordered @error('pin') input-error @enderror" placeholder="••••" />
                    @error('pin') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                <div class="modal-action">
                    <button type="button" wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Create Employee' }}</span>
                        <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif

    {{-- Confirm Delete Modal --}}
    @if($confirmDelete)
    <div class="modal modal-open">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete Employee</h3>
            <p class="py-4 text-base-content/70">Are you sure? This action cannot be undone.</p>
            <div class="modal-action">
                <button wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                <button wire:click="destroy" class="btn btn-error" wire:loading.attr="disabled">Delete</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif
</div>
