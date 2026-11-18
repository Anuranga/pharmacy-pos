@extends('layouts.app')

@section('title', 'Super Admin - Multi-Tenant Control Center')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
     x-data="{
         createModal: false,
         editModal: false,
         editTenantId: null,
         editName: '',
         editPhone: '',
         editAddress: '',
         editAdminEmail: ''
     }">

    <!-- Top Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white rounded-3xl p-6 border border-slate-700 shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-amber-500/20 text-amber-300 border border-amber-500/40">
                    <i class="fa-solid fa-crown mr-1"></i> Platform Super Administrator
                </span>
            </div>
            <h1 class="text-2xl font-extrabold font-display tracking-tight mt-1.5">
                Multi-Tenant Pharmacy Control Center
            </h1>
            <p class="text-xs text-slate-300 mt-0.5">
                Create new pharmacy tenants, monitor shop-isolated inventory & revenue, and enable or disable tenant access in real time.
            </p>
        </div>

        <button type="button"
                @click="createModal = true"
                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg flex items-center gap-2 transition-all cursor-pointer shrink-0">
            <i class="fa-solid fa-plus-circle"></i>
            <span>+ Create Pharmacy Tenant & Admin</span>
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Pharmacy Tenants</div>
            <div class="text-2xl font-black font-display text-slate-900 mt-1 font-mono">{{ $stats['total_tenants'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Isolated shop environments</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-emerald-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Active Tenants</div>
            <div class="text-2xl font-black font-display text-emerald-700 mt-1 font-mono">{{ $stats['active_tenants'] }}</div>
            <div class="text-[11px] text-emerald-600 mt-0.5">Allowed to sign in & bill</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-rose-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Disabled Tenants</div>
            <div class="text-2xl font-black font-display text-rose-600 mt-1 font-mono">{{ $stats['disabled_tenants'] }}</div>
            <div class="text-[11px] text-rose-500 mt-0.5">Admin & staff login blocked</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Tenant Users</div>
            <div class="text-2xl font-black font-display text-indigo-700 mt-1 font-mono">{{ $stats['total_users'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Admins, Managers, Cashiers & Staff</div>
        </div>
    </div>

    <!-- Tenants Roster Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
            <div>
                <h2 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-store text-emerald-600"></i>
                    <span>Registered Pharmacy Tenants (Data Isolated per Shop)</span>
                </h2>
                <p class="text-[11px] text-slate-500">Disabling a tenant immediately blocks its Admin, Managers, Cashiers, and Staff from logging in.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Tenant Code</th>
                        <th class="py-3 px-4">Pharmacy / Shop Name</th>
                        <th class="py-3 px-4">Owner Admin Account</th>
                        <th class="py-3 px-4 text-center">Staff Users</th>
                        <th class="py-3 px-4 text-center">Shop Items</th>
                        <th class="py-3 px-4 text-right">Shop Revenue</th>
                        <th class="py-3 px-4 text-center">Tenant Status</th>
                        <th class="py-3 px-4 text-right">Super Admin Controls</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @foreach($tenants as $row)
                    @php
                        $t = $row['tenant'];
                        $adm = $row['admin_user'];
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors {{ !$t->is_active ? 'bg-rose-50/30' : '' }}">
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                            {{ $t->code }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900 text-sm">{{ $t->name }}</div>
                            <div class="text-[11px] text-slate-400">
                                {{ $t->phone ?: 'No phone' }} &bull; {{ $t->address ?: 'No address' }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($adm)
                                <div class="font-bold text-slate-800">{{ $adm->name }}</div>
                                <div class="text-[11px] font-mono text-emerald-700">{{ $adm->email }}</div>
                            @else
                                <span class="text-slate-400 italic">No Admin Assigned</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center font-mono font-bold">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800">{{ $row['users_count'] }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-center font-mono font-bold text-indigo-700">
                            {{ number_format($row['products_count']) }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                            Rs. {{ number_format($row['total_revenue'], 2) }}
                            <span class="block text-[10px] text-slate-400 font-normal">{{ $row['sales_count'] }} bills</span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($t->is_active)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Enabled
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> Disabled
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <!-- Enable / Disable Toggle with SweetAlert2 Confirmation -->
                                <form method="POST" action="{{ route('super-admin.tenants.toggle', $t->id) }}" class="inline"
                                      @submit.prevent="confirmToggleTenant($event, '{{ addslashes($t->name) }}', {{ $t->is_active ? 'true' : 'false' }})">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $t->is_active ? 'bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-200' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs' }}">
                                        <i class="fa-solid {{ $t->is_active ? 'fa-ban' : 'fa-circle-check' }} mr-1"></i>
                                        {{ $t->is_active ? 'Disable Tenant' : 'Enable Tenant' }}
                                    </button>
                                </form>

                                <!-- Edit Tenant / Reset Admin Password -->
                                <button type="button"
                                        @click="editModal = true; editTenantId = {{ $t->id }}; editName = '{{ addslashes($t->name) }}'; editPhone = '{{ addslashes($t->phone ?? '') }}'; editAddress = '{{ addslashes($t->address ?? '') }}'; editAdminEmail = '{{ addslashes($adm->email ?? '') }}';"
                                        class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold"
                                        title="Edit Tenant & Reset Admin Password">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                @if($adm && $t->is_active)
                                <a href="{{ route('auth.switch', $adm->id) }}"
                                   class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 text-xs font-bold transition-colors"
                                   title="Enter this Pharmacy Tenant as Admin">
                                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Enter Shop
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Create New Pharmacy Tenant & Admin -->
    <div x-show="createModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200" @click.outside="createModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-store text-emerald-600"></i>
                    <span>Create New Pharmacy Tenant & Admin</span>
                </h3>
                <button @click="createModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('super-admin.tenants.store') }}" class="space-y-4 mt-4 text-xs">
                @csrf
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <div class="font-bold text-emerald-800 uppercase tracking-wider text-[11px]">1. Pharmacy Shop Details</div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Pharmacy Name *</label>
                        <input type="text" name="pharmacy_name" required placeholder="e.g. HealthFirst Pharmacy" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-bold">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Phone</label>
                            <input type="text" name="pharmacy_phone" placeholder="+94 7X..." class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Address</label>
                            <input type="text" name="pharmacy_address" placeholder="City / Street" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl">
                        </div>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <div class="font-bold text-indigo-800 uppercase tracking-wider text-[11px]">2. Tenant Admin Credentials</div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Admin Full Name *</label>
                        <input type="text" name="admin_name" required placeholder="e.g. Mr. Nimal Silva" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Admin Email *</label>
                            <input type="email" name="admin_email" required placeholder="admin@healthfirst.com" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Initial Password *</label>
                            <input type="text" name="admin_password" required value="password" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-mono">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="createModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">Create Tenant & Admin</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Tenant & Reset Admin Password -->
    <div x-show="editModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="editModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                    <span>Edit Tenant & Reset Admin Password</span>
                </h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" :action="'/super-admin/tenants/' + editTenantId" class="space-y-3 mt-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Pharmacy Name *</label>
                    <input type="text" name="name" x-model="editName" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" x-model="editPhone" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Address</label>
                        <input type="text" name="address" x-model="editAddress" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 space-y-1.5">
                    <div class="font-bold text-amber-900 text-[11px]">
                        Reset Admin Password (<span class="font-mono" x-text="editAdminEmail"></span>)
                    </div>
                    <input type="text" name="admin_password" placeholder="Leave blank to keep current password..."
                           class="w-full px-3 py-1.5 bg-white border border-amber-300 rounded-lg font-mono">
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="editModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function confirmToggleTenant(event, tenantName, isCurrentlyActive) {
    const form = event.target;
    if (isCurrentlyActive) {
        Swal.fire({
            title: 'Disable Pharmacy Tenant?',
            html: `Are you sure you want to disable <strong>${tenantName}</strong>?<br><span class="text-xs text-slate-500 mt-1 block">Its Admin, Managers, Cashiers, and Staff will be immediately blocked from logging in until re-enabled.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-ban mr-1"></i> Yes, Disable Tenant',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    } else {
        Swal.fire({
            title: 'Enable Pharmacy Tenant?',
            html: `Restore full login and billing access for <strong>${tenantName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-circle-check mr-1"></i> Yes, Enable Tenant',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
}
</script>
@endpush
@endsection
