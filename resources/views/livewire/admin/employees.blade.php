<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public bool $showModal = false;
    public bool $showDeleteModal = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $name = '';
    public array $adminIds = [];

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
        $this->adminIds = $user->admins()->pluck('users.id')->map(fn($id) => (string) $id)->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = ['name' => 'required|string|max:255'];

        if (auth()->user()->isSuperAdmin()) {
            $rules['adminIds']   = 'required|array|min:1';
            $rules['adminIds.*'] = 'exists:users,id';
        } else {
            $rules['adminIds']   = 'array';
            $rules['adminIds.*'] = 'exists:users,id';
        }

        $this->validate($rules);

        $superAdminId = (string) User::where('is_super', true)->value('id');

        if ($this->editingId) {
            $employee = User::findOrFail($this->editingId);
            $employee->update(['name' => $this->name]);
        } else {
            $employee = User::create([
                'name' => $this->name,
                'role' => 'employee',
            ]);
        }

        if (auth()->user()->isSuperAdmin()) {
            $employee->admins()->sync($this->adminIds);
        } else {
            // Regular admin: force themselves + super admin, plus any extras they selected
            $ids = array_unique(array_merge(
                $this->adminIds,
                [(string) auth()->id(), $superAdminId]
            ));
            $employee->admins()->sync(array_filter($ids));
        }

        $this->showModal = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function destroy(): void
    {
        if ($this->deletingId) {
            User::findOrFail($this->deletingId)->delete();
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    public function closeModals(): void
    {
        $this->showModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->adminIds = [];
        $this->editingId = null;
        $this->resetValidation();
    }

    public function with(): array
    {
        if (auth()->user()->isSuperAdmin()) {
            $employees = User::where('role', 'employee')
                ->with('admins:id,name,is_super')
                ->orderBy('name')
                ->paginate(15);
        } else {
            $employees = auth()->user()->employees()
                ->with('admins:id,name,is_super')
                ->orderBy('name')
                ->paginate(15);
        }

        $admins = User::where('role', 'admin')->orderBy('name')->get(['id', 'name', 'is_super']);

        return compact('employees', 'admins');
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
                    @if(auth()->user()->isSuperAdmin())
                        <th>Admins</th>
                    @endif
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td class="text-base-content/50 text-sm">{{ $employee->id }}</td>
                        <td class="font-medium">{{ $employee->name }}</td>
                        @if(auth()->user()->isSuperAdmin())
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($employee->admins as $admin)
                                        <span class="badge badge-ghost badge-sm">
                                            {{ $admin->name }}
                                            @if($admin->is_super)
                                                <span class="ml-1 text-warning">★</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        @endif
                        <td class="text-right space-x-1">
                            <button wire:click="openEdit({{ $employee->id }})" class="btn btn-ghost btn-xs">Edit</button>
                            <button wire:click="confirmDelete({{ $employee->id }})" class="btn btn-ghost btn-xs text-error">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 4 : 3 }}" class="text-center text-base-content/50 py-8">No employees yet.</td>
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
    <div class="modal modal-open" @click.self="$wire.closeModals()">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg mb-4">{{ $editingId ? 'Edit Employee' : 'New Employee' }}</h3>
            <form wire:submit="save" class="space-y-4">

                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Name</span></label>
                    <input wire:model="name" type="text" class="input input-bordered @error('name') input-error @enderror"
                           placeholder="Full name" autofocus />
                    @error('name') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                {{-- Admin visibility checkboxes --}}
                @if(auth()->user()->isSuperAdmin())
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Visible to Admins</span></label>
                    <div class="border border-base-300 rounded-box p-3 max-h-40 overflow-y-auto space-y-1">
                        @foreach($admins as $admin)
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg cursor-pointer hover:bg-base-200">
                                <input type="checkbox" wire:model="adminIds" value="{{ $admin->id }}"
                                       class="checkbox checkbox-sm checkbox-primary" />
                                <span class="text-sm">{{ $admin->name }}</span>
                                @if($admin->is_super)
                                    <span class="badge badge-xs badge-warning ml-auto">super</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @error('adminIds') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>
                @else
                {{-- Regular admin: show other admins they can optionally share with (themselves + super always forced) --}}
                @php $otherAdmins = $admins->filter(fn($a) => !$a->is_super && $a->id !== auth()->id()); @endphp
                @if($otherAdmins->isNotEmpty())
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Also visible to</span></label>
                    <div class="border border-base-300 rounded-box p-3 space-y-1">
                        @foreach($otherAdmins as $admin)
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg cursor-pointer hover:bg-base-200">
                                <input type="checkbox" wire:model="adminIds" value="{{ $admin->id }}"
                                       class="checkbox checkbox-sm checkbox-primary" />
                                <span class="text-sm">{{ $admin->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif

                <div class="modal-action">
                    <button type="button" wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Create Employee' }}</span>
                        <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Confirm Delete Modal --}}
    @if($showDeleteModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg">Delete Employee</h3>
            <p class="py-4 text-base-content/70">Are you sure? This action cannot be undone.</p>
            <div class="modal-action">
                <button type="button" wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                <button type="button" wire:click="destroy" class="btn btn-error" wire:loading.attr="disabled">Delete</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif
</div>
