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
    public string $pin = '';
    public string $pin_confirmation = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->pin = '';
        $this->pin_confirmation = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $rules = ['name' => 'required|string|max:255'];

        if (!$this->editingId || $this->pin !== '') {
            $rules['pin'] = [
                'required', 'digits:6', 'confirmed',
                \Illuminate\Validation\Rule::unique('users', 'pin')->ignore($this->editingId),
            ];
            $rules['pin_confirmation'] = 'required';
        }

        $this->validate($rules);

        if ($this->editingId) {
            $data = ['name' => $this->name];
            if ($this->pin !== '') $data['pin'] = $this->pin;
            User::findOrFail($this->editingId)->update($data);
        } else {
            User::create([
                'name' => $this->name,
                'pin'  => $this->pin,
                'role' => 'admin',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        if ($id === auth()->id()) return;
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function destroy(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        if ($this->deletingId && $this->deletingId !== auth()->id()) {
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
        $this->editingId = null;
        $this->name = '';
        $this->pin = '';
        $this->pin_confirmation = '';
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'admins' => User::where('role', 'admin')->orderBy('name')->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Administrators</h2>
        <button wire:click="openCreate" class="btn btn-primary btn-sm">+ Add Administrator</button>
    </div>

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
                @forelse($admins as $admin)
                    <tr>
                        <td class="text-base-content/50 text-sm">{{ $admin->id }}</td>
                        <td class="font-medium">
                            {{ $admin->name }}
                            @if($admin->id === auth()->id())
                                <span class="badge badge-primary badge-sm ml-1">You</span>
                            @endif
                        </td>
                        <td class="text-base-content/30 font-mono text-sm">••••••</td>
                        <td class="text-right space-x-1">
                            <button wire:click="openEdit({{ $admin->id }})" class="btn btn-ghost btn-xs">Edit</button>
                            @if($admin->id !== auth()->id())
                                <button wire:click="confirmDelete({{ $admin->id }})" class="btn btn-ghost btn-xs text-error">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-base-content/50 py-8">No administrators found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($admins->hasPages())
            <div class="px-4 py-3 border-t border-base-200">{{ $admins->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if($showModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg mb-4">{{ $editingId ? 'Edit Administrator' : 'New Administrator' }}</h3>
            <form wire:submit="save" class="space-y-4">

                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Name</span></label>
                    <input wire:model="name" type="text"
                           class="input input-bordered @error('name') input-error @enderror"
                           placeholder="Full name" autofocus />
                    @error('name') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium">PIN (6 digits)</span>
                        @if($editingId)
                            <span class="label-text-alt text-base-content/50">Leave blank to keep current</span>
                        @endif
                    </label>
                    <input wire:model="pin" type="password" inputmode="numeric" maxlength="6"
                           class="input input-bordered font-mono @error('pin') input-error @enderror"
                           placeholder="••••••" autocomplete="new-password" />
                    @error('pin') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Confirm PIN</span></label>
                    <input wire:model="pin_confirmation" type="password" inputmode="numeric" maxlength="6"
                           class="input input-bordered font-mono @error('pin_confirmation') input-error @enderror"
                           placeholder="••••••" autocomplete="new-password" />
                    @error('pin_confirmation') <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label> @enderror
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Create Administrator' }}</span>
                        <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif

    {{-- Confirm Delete --}}
    @if($showDeleteModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg">
            <h3 class="font-bold text-lg">Delete Administrator</h3>
            <p class="py-4 text-base-content/70">This action cannot be undone. The administrator's tasks will remain in the system.</p>
            <div class="modal-action">
                <button type="button" wire:click="closeModals" class="btn btn-ghost">Cancel</button>
                <button type="button" wire:click="destroy" class="btn btn-error" wire:loading.attr="disabled">Delete</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif
</div>
