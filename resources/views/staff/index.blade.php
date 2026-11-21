@extends('layouts.app')

@section('title', 'Staff & Role-Wise Permissions - ' . (auth()->user()->tenant->name ?? 'Pharmacy'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
     x-data="{
         addStaffModal: false,
         editStaffModal: false,
         passwordModal: false,
         selectedUser: { id: null, name: '', email: '', phone: '', role_name: 'cashier', status: 'active' }
     }">

    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <i class="fa-solid fa-store mr-1"></i> Tenant: {{ auth()->user()->tenant->name ?? 'Main Pharmacy' }}
                </span>
                <span class="text-xs font-mono text-slate-400">Code: {{ auth()->user()->tenant->code ?? 'PHARMA-001' }}</span>
            </div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-user-gear text-emerald-600"></i>
                <span>Shop Staff & Role-Wise Permission Matrix</span>
            </h1>
            <p class="ui-subheading text-xs text-slate-500 mt-0.5">
                Add Managers, Cashiers, and Staff for your pharmacy, change their passwords, and configure role-wise access permissions via checkboxes.
            </p>
        </div>

        <button type="button"
                @click="addStaffModal = true"
                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-2 transition-all cursor-pointer shrink-0">
            <i class="fa-solid fa-user-plus"></i>
            <span>+ Add Manager / Cashier / Staff</span>
        </button>
    </div>

    <!-- SECTION 1: Pharmacy Staff Roster (Managers, Cashiers, Staff) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/60 flex items-center justify-between">
            <div>
                <h2 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-users text-emerald-600"></i>
                    <span>1. Pharmacy Team Accounts ({{ $staffMembers->count() }})</span>
                </h2>
                <p class="text-[11px] text-slate-500">All users listed below belong exclusively to <strong>{{ auth()->user()->tenant->name ?? 'your pharmacy' }}</strong>.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Full Name</th>
                        <th class="py-3 px-4">Login Email</th>
                        <th class="py-3 px-4">Phone</th>
                        <th class="py-3 px-4">Assigned Role</th>
                        <th class="py-3 px-4 text-center">Account Status</th>
                        <th class="py-3 px-4 text-right">Admin Actions (Password & Edit)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @foreach($staffMembers as $member)
                    @php
                        $rName = strtolower($member->role->name ?? 'staff');
                        $badgeClass = match($rName) {
                            'admin' => 'bg-purple-100 text-purple-800 border-purple-300',
                            'manager' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                            'cashier' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            default => 'bg-sky-100 text-sky-800 border-sky-300',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            {{ $member->name }}
                            @if($member->id === auth()->id())
                                <span class="ml-1 text-[10px] font-mono px-1.5 py-0.2 rounded bg-slate-200 text-slate-700">You</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-700">{{ $member->email }}</td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $member->phone ?: '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $badgeClass }}">
                                {{ $member->role->label ?? ucfirst($rName) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($member->status === 'active')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Disabled</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button"
                                        @click="passwordModal = true; selectedUser = { id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', role_name: '{{ addslashes($member->role->label ?? $rName) }}' };"
                                        class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-500 text-amber-800 hover:text-white border border-amber-200 text-xs font-bold transition-colors cursor-pointer">
                                    <i class="fa-solid fa-key mr-1"></i> Change Password
                                </button>

                                <button type="button"
                                        @click="editStaffModal = true; selectedUser = { id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', email: '{{ addslashes($member->email) }}', phone: '{{ addslashes($member->phone ?? '') }}', role_name: '{{ $rName }}', status: '{{ $member->status }}' };"
                                        class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 2: Logical Role-Wise Permission Checkboxes Matrix -->
    <form method="POST" action="{{ route('staff.permissions.update') }}"
          @submit.prevent="confirmSavePermissions($event)"
          class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf

        <div class="px-6 py-4 border-b border-slate-200 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-display font-bold text-base text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                    <span>2. Role-Wise Permissions Matrix (Logical Checkboxes)</span>
                </h2>
                <p class="text-xs text-slate-300 mt-0.5">
                    Tick or untick which modules and actions each role (<strong>Manager</strong>, <strong>Cashier</strong>, and <strong>Staff</strong>) is allowed to access in your pharmacy.
                </p>
            </div>

            <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg flex items-center gap-2 transition-all cursor-pointer shrink-0">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save Role Permissions</span>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-5 w-1/2">System Capability / Permission</th>
                        <th class="py-3.5 px-4 text-center w-1/6">
                            <span class="px-2.5 py-1 rounded-lg bg-purple-100 text-purple-800 border border-purple-300">Admin</span>
                        </th>
                        <th class="py-3.5 px-4 text-center w-1/6">
                            <span class="px-2.5 py-1 rounded-lg bg-indigo-100 text-indigo-800 border border-indigo-300">Manager</span>
                        </th>
                        <th class="py-3.5 px-4 text-center w-1/6">
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300">Cashier</span>
                        </th>
                        <th class="py-3.5 px-4 text-center w-1/6">
                            <span class="px-2.5 py-1 rounded-lg bg-sky-100 text-sky-800 border border-sky-300">Staff</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($permissionGroups as $groupTitle => $perms)
                    <!-- Group Header Row -->
                    <tr class="bg-slate-50/90">
                        <td colspan="5" class="py-2.5 px-5 font-display font-extrabold text-xs uppercase tracking-wider text-slate-800">
                            <i class="fa-solid fa-layer-group text-emerald-600 mr-1.5"></i> {{ $groupTitle }}
                        </td>
                    </tr>

                    @foreach($perms as $permKey => $permLabel)
                    <tr class="hover:bg-emerald-50/30 transition-colors">
                        <td class="py-3 px-5">
                            <div class="font-bold text-slate-800">{{ $permLabel }}</div>
                            <div class="text-[10px] font-mono text-slate-400">{{ $permKey }}</div>
                        </td>

                        <!-- Admin (Always Full Access) -->
                        <td class="py-3 px-4 text-center">
                            <input type="checkbox" checked disabled class="w-4 h-4 rounded text-purple-600 bg-slate-100 border-slate-300 cursor-not-allowed">
                        </td>

                        <!-- Manager Checkbox -->
                        <td class="py-3 px-4 text-center">
                            <input type="checkbox"
                                   name="permissions[manager][]"
                                   value="{{ $permKey }}"
                                   @change="onPermissionToggle($event, '{{ addslashes($permLabel) }}', 'Pharmacy Manager')"
                                   {{ in_array($permKey, $rolePermissions['manager'] ?? [], true) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 cursor-pointer">
                        </td>

                        <!-- Cashier Checkbox -->
                        <td class="py-3 px-4 text-center">
                            <input type="checkbox"
                                   name="permissions[cashier][]"
                                   value="{{ $permKey }}"
                                   @change="onPermissionToggle($event, '{{ addslashes($permLabel) }}', 'POS Cashier')"
                                   {{ in_array($permKey, $rolePermissions['cashier'] ?? [], true) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer">
                        </td>

                        <!-- Staff Checkbox -->
                        <td class="py-3 px-4 text-center">
                            <input type="checkbox"
                                   name="permissions[staff][]"
                                   value="{{ $permKey }}"
                                   @change="onPermissionToggle($event, '{{ addslashes($permLabel) }}', 'Pharmacy Staff')"
                                   {{ in_array($permKey, $rolePermissions['staff'] ?? [], true) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-sky-600 border-slate-300 focus:ring-sky-500 cursor-pointer">
                        </td>
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <span class="text-xs text-slate-500">
                <i class="fa-solid fa-circle-info text-emerald-600 mr-1"></i> Changes take effect immediately for all Managers, Cashiers, and Staff in your pharmacy.
            </span>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md transition-all cursor-pointer">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save Role-Wise Permissions
            </button>
        </div>
    </form>

    <!-- SECTION 3: Admin UI & Dashboard Content Visibility Controls (Enable / Disable Sub-Headings & Dashboard Widgets) -->
    @php
        $uiSettingDefinitions = $uiSettingDefinitions ?? \App\Models\TenantRolePermission::uiSettingDefinitions();
        $uiSettings = $uiSettings ?? \App\Models\TenantRolePermission::getUiSettingsForTenant(auth()->user()->tenant_id ?? null);
    @endphp
    <form method="POST" action="{{ route('staff.ui-settings.update') }}"
          class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf
        <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-slate-900 to-purple-950 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-display font-bold text-base text-white flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-purple-300"></i>
                    <span>3. Interface & Dashboard Content Controls (Enable / Disable)</span>
                </h2>
                <p class="ui-subheading text-xs text-slate-300 mt-0.5">
                    Sub-heading descriptions are disabled by default for a clean interface. Tick or untick below to enable/disable sub-headings and dashboard widgets.
                </p>
            </div>

            <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg flex items-center gap-2 transition-all cursor-pointer shrink-0">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save UI & Dashboard Settings</span>
            </button>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-3.5 text-xs">
            @foreach($uiSettingDefinitions as $settingKey => $meta)
            <label class="flex items-start gap-3 p-4 rounded-2xl border transition-colors cursor-pointer {{ !empty($uiSettings[$settingKey]) ? 'bg-emerald-50/40 border-emerald-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70' }}">
                <input type="checkbox"
                       name="ui_settings[]"
                       value="{{ $settingKey }}"
                       {{ !empty($uiSettings[$settingKey]) ? 'checked' : '' }}
                       class="mt-0.5 w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer">
                <div class="flex-1">
                    <div class="font-bold text-slate-900 flex items-center gap-2">
                        <span>{{ $meta['label'] }}</span>
                        @if($settingKey === 'show_subheadings')
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-amber-100 text-amber-800">Default: Disabled</span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">{{ $meta['description'] }}</p>
                </div>
            </label>
            @endforeach
        </div>
    </form>

    <!-- MODAL 1: Add New Manager / Cashier / Staff -->
    <div x-show="addStaffModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="addStaffModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-emerald-600"></i>
                    <span>Add Team Member ({{ auth()->user()->tenant->name ?? 'Shop' }})</span>
                </h3>
                <button @click="addStaffModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('staff.store') }}" class="space-y-3.5 mt-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Ruwan Jayasinghe" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-semibold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Login Email *</label>
                        <input type="email" name="email" required placeholder="staff@pharmacy.com" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" placeholder="+94 7X..." class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Select Role *</label>
                        <select name="role_name" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-bold text-slate-800">
                            <option value="manager">Pharmacy Manager</option>
                            <option value="cashier" selected>POS Cashier</option>
                            <option value="staff">Pharmacy Staff</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Login Password *</label>
                        <input type="text" name="password" required minlength="6" value="password" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addStaffModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Change Password for Manager / Cashier / Staff -->
    <div x-show="passwordModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200" @click.outside="passwordModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-key text-amber-500"></i>
                    <span>Change User Password</span>
                </h3>
                <button @click="passwordModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" :action="'/staff/' + selectedUser.id + '/password'" class="space-y-4 mt-4 text-xs">
                @csrf
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="text-[10px] uppercase text-slate-400 font-bold">Staff Member</div>
                    <div class="font-bold text-sm text-slate-900" x-text="selectedUser.name"></div>
                    <div class="text-[11px] text-emerald-700 font-semibold" x-text="selectedUser.role_name"></div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">New Password (Min 6 characters) *</label>
                    <input type="text" name="new_password" required minlength="6" placeholder="Enter new password..."
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="passwordModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-sm">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Edit Staff Details / Role / Status -->
    <div x-show="editStaffModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="editStaffModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-emerald-600"></i>
                    <span>Edit Staff Member</span>
                </h3>
                <button @click="editStaffModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" :action="'/staff/' + selectedUser.id" class="space-y-3.5 mt-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="name" x-model="selectedUser.name" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-semibold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email *</label>
                        <input type="email" name="email" x-model="selectedUser.email" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" x-model="selectedUser.phone" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <template x-if="selectedUser.role_name !== 'admin'">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Role *</label>
                            <select name="role_name" x-model="selectedUser.role_name" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-bold">
                                <option value="manager">Pharmacy Manager</option>
                                <option value="cashier">POS Cashier</option>
                                <option value="staff">Pharmacy Staff</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status *</label>
                            <select name="status" x-model="selectedUser.status" class="w-full px-3 py-2 border border-slate-300 rounded-xl font-bold">
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </div>
                </template>
                <template x-if="selectedUser.role_name === 'admin'">
                    <input type="hidden" name="status" value="active">
                </template>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="editStaffModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function onPermissionToggle(event, permLabel, roleLabel) {
    const checkbox = event.target;
    // When unticking (removing) a permission, ask for confirmation
    if (!checkbox.checked) {
        Swal.fire({
            title: 'Remove Permission?',
            html: `Are you sure you want to remove <strong>"${permLabel}"</strong> from <strong>${roleLabel}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Remove Permission',
            cancelButtonText: 'Keep Permission',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                checkbox.checked = true;
            }
        });
    }
}

function confirmSavePermissions(event) {
    const form = event.target;
    Swal.fire({
        title: 'Save Role-Wise Permissions?',
        html: 'This will immediately update module access and permissions for all <strong>Managers</strong>, <strong>Cashiers</strong>, and <strong>Staff</strong> in your pharmacy.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-floppy-disk mr-1"></i> Yes, Save Permissions',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}
</script>
@endpush
@endsection
