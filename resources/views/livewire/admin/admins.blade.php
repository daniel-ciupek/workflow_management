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

<div class="page-enter">
    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Administrators</h1>
            <p class="text-slate-500 text-sm mt-0.5">Manage administrator accounts and PINs</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Administrator
        </button>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">#</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Name</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">PIN</th>
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($admins as $admin)
                        <tr class="hover:bg-slate-50 transition-colors duration-100">
                            <td class="px-5 py-3.5 text-xs text-slate-400 font-mono">{{ $admin->id }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-slate-900">{{ $admin->name }}</span>
                                    @if($admin->id === auth()->id())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">You</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-slate-300 font-mono text-sm tracking-widest">••••••</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="openEdit({{ $admin->id }})"
                                            class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors duration-150"
                                            title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @if($admin->id !== auth()->id())
                                        <button wire:click="confirmDelete({{ $admin->id }})"
                                                class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors duration-150"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <p class="text-sm text-slate-500">No administrators found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($admins->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $admins->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if($showModal)
    <div class="modal modal-open">
        <div class="modal-box w-11/12 max-w-lg rounded-2xl p-0 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h3 class="font-semibold text-slate-900 text-lg">{{ $editingId ? 'Edit Administrator' : 'New Administrator' }}</h3>
                <button wire:click="closeModals" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5">
            <form wire:submit="save" class="space-y-4" id="adminForm">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text"
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('name') border-red-400 bg-red-50 @enderror"
                           placeholder="Full name" autofocus />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-slate-700">PIN (6 digits)</label>
                        @if($editingId)
                            <span class="text-xs text-slate-400">Leave blank to keep current</span>
                        @endif
                    </div>
                    <input wire:model="pin" type="password" inputmode="numeric" maxlength="6"
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('pin') border-red-400 bg-red-50 @enderror"
                           placeholder="••••••" autocomplete="new-password" />
                    @error('pin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm PIN</label>
                    <input wire:model="pin_confirmation" type="password" inputmode="numeric" maxlength="6"
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('pin_confirmation') border-red-400 bg-red-50 @enderror"
                           placeholder="••••••" autocomplete="new-password" />
                    @error('pin_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </form>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" wire:click="closeModals"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors duration-150">
                    Cancel
                </button>
                <button type="submit" form="adminForm"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-150 disabled:opacity-60"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Create Administrator' }}</span>
                    <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeModals"></div>
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
                    <h3 class="font-semibold text-slate-900">Delete Administrator</h3>
                </div>
                <p class="text-sm text-slate-600">This action cannot be undone. The administrator's tasks will remain in the system.</p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" wire:click="closeModals"
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
        <div class="modal-backdrop" wire:click="closeModals"></div>
    </div>
    @endif
</div>
