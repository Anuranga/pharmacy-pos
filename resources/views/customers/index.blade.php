@extends('layouts.app')

@section('title', 'Customer & Debtor Management')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
     x-data="{ 
        addCustomerModal: false, 
        editCustomerModal: false,
        editForm: {
            id: '',
            name: '',
            phone: '',
            nic_number: '',
            credit_limit: 0,
            address: ''
        },
        openEditModal(customer) {
            this.editForm = {
                id: customer.id,
                name: customer.name,
                phone: customer.phone,
                nic_number: customer.nic_number || '',
                credit_limit: customer.credit_limit,
                address: customer.address || ''
            };
            this.editCustomerModal = true;
        }
     }">
    
    <!-- Top Action Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-users text-emerald-600"></i>
                <span>Customer Directory & Debtor Lending</span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Register new customers, edit customer profiles, manage approved credit limits, and track ledger tabs.</p>
        </div>

        <button type="button" @click="addCustomerModal = true"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add New Customer</span>
        </button>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Accounts Receivable</span>
                <div class="text-2xl font-black font-display text-rose-600 font-mono mt-1">
                    Rs. {{ number_format($totalReceivable, 2) }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Registered Customers</span>
                <div class="text-2xl font-black font-display text-slate-900 mt-1">
                    {{ $customers->total() }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-address-book"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Aging Breakdown</span>
                <div class="mt-1">
                    <a href="{{ route('reports.customer-aging') }}" class="text-xs font-bold text-emerald-600 hover:underline flex items-center gap-1">
                        View 0-90+ Days Aging Breakdown &rarr;
                    </a>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('customers.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs">
            <div class="md:col-span-8">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer name, phone number, NIC..."
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="md:col-span-2 flex items-center">
                <label class="flex items-center space-x-2 text-slate-700 font-medium cursor-pointer">
                    <input type="checkbox" name="filter" value="with_debt" {{ request('filter') === 'with_debt' ? 'checked' : '' }} class="rounded text-emerald-600">
                    <span>Outstanding Debt Only</span>
                </label>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Customer Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">Customer Name</th>
                        <th class="py-3.5 px-4">Phone Number</th>
                        <th class="py-3.5 px-4">NIC Number</th>
                        <th class="py-3.5 px-4 text-right">Approved Credit Limit</th>
                        <th class="py-3.5 px-4 text-right">Outstanding Tab</th>
                        <th class="py-3.5 px-4 text-right">Remaining Credit</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            <a href="{{ route('customers.show', $c->id) }}" class="hover:text-emerald-600 text-sm">
                                {{ $c->name }}
                            </a>
                            <span class="text-[10px] text-slate-400 block font-normal">{{ $c->address ?? 'No address registered' }}</span>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $c->phone }}</td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $c->nic_number ?? '—' }}</td>
                        <td class="py-3 px-4 text-right font-mono font-semibold text-slate-700">
                            Rs. {{ number_format($c->credit_limit, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $c->current_debt_balance > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rs. {{ number_format($c->current_debt_balance, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                            Rs. {{ number_format($c->remaining_credit, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" 
                                        @click="openEditModal({ id: {{ $c->id }}, name: @js($c->name), phone: @js($c->phone), nic_number: @js($c->nic_number), credit_limit: {{ (float)$c->credit_limit }}, address: @js($c->address) })"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors" 
                                        title="Edit Customer Details">
                                    <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                                    <span>Edit</span>
                                </button>
                                <a href="{{ route('customers.show', $c->id) }}" 
                                   class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold transition-colors">
                                    Ledger &rarr;
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            No customers found. Click "Add New Customer" to register one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>

    <!-- MODAL 1: Add New Customer Modal -->
    <div x-show="addCustomerModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="addCustomerModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-emerald-600"></i>
                    <span>Register New Customer</span>
                </h3>
                <button @click="addCustomerModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('customers.store') }}" class="space-y-3 mt-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Customer Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Sunil Perera" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone Number *</label>
                        <input type="text" name="phone" required placeholder="+94 7X..." class="w-full px-3 py-2 border rounded-xl font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">National ID (NIC)</label>
                        <input type="text" name="nic_number" placeholder="e.g. 1985..." class="w-full px-3 py-2 border rounded-xl font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Approved Credit Limit (Rs.) *</label>
                    <input type="number" min="0" step="100" name="credit_limit" value="15000" required class="w-full px-3 py-2 border rounded-xl font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Maximum amount customer is allowed to borrow on tab.</span>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Residential Address</label>
                    <textarea name="address" rows="2" placeholder="Street, city, landmark..." class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="addCustomerModal = false" class="px-4 py-2 border rounded-xl text-slate-600 hover:bg-slate-50 font-medium">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Customer Details Modal -->
    <div x-show="editCustomerModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="editCustomerModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                    <span>Edit Customer Details</span>
                </h3>
                <button @click="editCustomerModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" :action="'/customers/' + editForm.id" class="space-y-3 mt-3 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Customer Full Name *</label>
                    <input type="text" name="name" x-model="editForm.name" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone Number *</label>
                        <input type="text" name="phone" x-model="editForm.phone" required class="w-full px-3 py-2 border rounded-xl font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">National ID (NIC)</label>
                        <input type="text" name="nic_number" x-model="editForm.nic_number" class="w-full px-3 py-2 border rounded-xl font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Approved Credit Limit (Rs.) *</label>
                    <input type="number" min="0" step="100" name="credit_limit" x-model.number="editForm.credit_limit" required class="w-full px-3 py-2 border rounded-xl font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Adjust approved credit ceiling for this customer.</span>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Residential Address</label>
                    <textarea name="address" x-model="editForm.address" rows="2" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="editCustomerModal = false" class="px-4 py-2 border rounded-xl text-slate-600 hover:bg-slate-50 font-medium">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">
                        Update Customer Details
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
