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
    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Employees</h1>
            <p class="text-slate-500 text-sm mt-0.5">Manage your team members</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Employee
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">#</th>
                        <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Name</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Admins</th>
                        @endif
                        <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3.5">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-slate-50 transition-colors duration-100">
                            <td class="px-5 py-3.5 text-xs text-slate-400 font-mono">{{ $employee->id }}</td>
                            <td class="px-5 py-3.5">
                                <span class="text-sm font-medium text-slate-900">{{ $employee->name }}</span>
                            </td>
                            @if(auth()->user()->isSuperAdmin())
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($employee->admins as $admin)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">
                                                {{ $admin->name }}
                                                @if($admin->is_super)
                                                    <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="openEdit({{ $employee->id }})"
                                            class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors duration-150"
                                            title="Edit employee">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $employee->id }})"
                                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors duration-150"
                                            title="Delete employee">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? 4 : 3 }}" class="px-5 py-12 text-center">
                                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-slate-500 font-medium">No employees yet</p>
                                <p class="text-xs text-slate-400 mt-0.5">Add your first team member to get started</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $employees->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if($showModal)
    <div class="modal modal-open" @click.self="$wire.closeModals()">
        <div class="modal-box w-11/12 max-w-lg rounded-2xl p-0 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h3 class="font-semibold text-slate-900 text-lg">{{ $editingId ? 'Edit Employee' : 'New Employee' }}</h3>
                <button wire:click="closeModals" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5">
            <form wire:submit="save" class="space-y-4" id="employeeForm">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text"
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors duration-150 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none @error('name') border-red-400 bg-red-50 @enderror"
                           placeholder="Full name" autofocus />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Admin visibility checkboxes --}}
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Visible to Admins <span class="text-red-500">*</span></label>
                    <div class="border border-slate-300 rounded-lg p-2 max-h-40 overflow-y-auto space-y-0.5">
                        @foreach($admins as $admin)
                            <label class="flex items-center gap-3 px-2 py-1.5 rounded-md cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" wire:model="adminIds" value="{{ $admin->id }}"
                                       class="checkbox checkbox-sm checkbox-primary" />
                                <span class="text-sm text-slate-700">{{ $admin->name }}</span>
                                @if($admin->is_super)
                                    <span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">super</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @error('adminIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @else
                @php $otherAdmins = $admins->filter(fn($a) => !$a->is_super && $a->id !== auth()->id()); @endphp
                @if($otherAdmins->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Also visible to</label>
                    <div class="border border-slate-300 rounded-lg p-2 space-y-0.5">
                        @foreach($otherAdmins as $admin)
                            <label class="flex items-center gap-3 px-2 py-1.5 rounded-md cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" wire:model="adminIds" value="{{ $admin->id }}"
                                       class="checkbox checkbox-sm checkbox-primary" />
                                <span class="text-sm text-slate-700">{{ $admin->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif

            </form>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" wire:click="closeModals"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors duration-150">
                    Cancel
                </button>
                <button type="submit" form="employeeForm"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-150 disabled:opacity-60"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Create Employee' }}</span>
                    <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Confirm Delete Modal --}}
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
                    <h3 class="font-semibold text-slate-900">Delete Employee</h3>
                </div>
                <p class="text-sm text-slate-600">Are you sure? This action cannot be undone.</p>
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
