@extends('layouts.app')

@section('title', 'Suppliers & Accounts Payable')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
     x-data="{
         addSupplierModal: false,
         adjustBalanceModal: false,
         adjustSupplier: { id: '', name: '', current_balance: 0 },
         openAdjustModal(sup) {
             this.adjustSupplier = sup;
             this.adjustBalanceModal = true;
         }
     }">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-building-wheat text-emerald-600"></i>
                <span>Suppliers & Accounts Payable (AP)</span>
            </h1>
            <p class="ui-subheading text-sm text-slate-500 mt-1">Manage pharmaceutical distributors, credit terms, running liabilities, and payment vouchers.</p>
        </div>

        <button type="button" @click="addSupplierModal = true"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Register New Supplier</span>
        </button>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Accounts Payable Liability</span>
                <div class="text-2xl font-black font-display text-rose-600 font-mono mt-1">
                    Rs. {{ number_format($totalPayable, 2) }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Distributors</span>
                <div class="text-2xl font-black font-display text-slate-900 mt-1">
                    {{ $suppliers->total() }}
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-700 flex items-center justify-center text-xl">
                <i class="fa-solid fa-truck-moving"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Supplier vs Transaction</span>
                <div class="mt-1">
                    <a href="{{ route('reports.supplier-vs-transaction') }}" class="text-xs font-bold text-emerald-600 hover:underline flex items-center gap-1">
                        View Purchase Orders & Payments &rarr;
                    </a>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chart-column"></i>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">Supplier / Distributor</th>
                        <th class="py-3.5 px-4">Contact Person</th>
                        <th class="py-3.5 px-4">Phone & Email</th>
                        <th class="py-3.5 px-4 text-center">Credit Period</th>
                        <th class="py-3.5 px-4 text-right">Outstanding Liability</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($suppliers as $s)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            <a href="{{ route('suppliers.show', $s->id) }}" class="hover:text-emerald-600 text-sm">
                                {{ $s->name }}
                            </a>
                            <span class="text-[10px] text-slate-400 block font-normal">{{ $s->address ?? 'No address' }}</span>
                        </td>
                        <td class="py-3 px-4 text-slate-700">{{ $s->contact_person ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="font-mono text-slate-700 block">{{ $s->phone }}</span>
                            <span class="text-[10px] text-slate-400 block">{{ $s->email ?? '' }}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                {{ $s->credit_period_days }} Days
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-sm {{ $s->current_balance > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rs. {{ number_format($s->current_balance, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button"
                                        @click="openAdjustModal({ id: {{ $s->id }}, name: @js($s->name), current_balance: {{ (float) $s->current_balance }} })"
                                        class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer"
                                        title="Adjust Past / Migrated Payable Balance">
                                    <i class="fa-solid fa-scale-balanced text-amber-600"></i>
                                    <span>Adjust Bal</span>
                                </button>
                                <a href="{{ route('suppliers.show', $s->id) }}" class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold transition-colors">
                                    Ledger & Pay &rarr;
                                </a>
                                <form method="POST" action="{{ route('suppliers.destroy', $s->id) }}" class="inline"
                                      onsubmit="confirmDeleteForm(event, '{{ addslashes($s->name) }}', 'Supplier Account')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white transition-colors text-xs cursor-pointer" title="Delete Supplier">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">No suppliers registered.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $suppliers->withQueryString()->links() }}
        </div>
    </div>

    <!-- Register Modal -->
    <div x-show="addSupplierModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="addSupplierModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-building-wheat text-emerald-600"></i>
                    <span>Register New Supplier</span>
                </h3>
                <button @click="addSupplierModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-3 mt-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Company / Distributor Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-xl">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Contact Person</label>
                        <input type="text" name="contact_person" class="w-full px-3 py-2 border rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 border rounded-xl">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Credit Period (Days) *</label>
                        <input type="number" min="0" name="credit_period_days" value="30" required class="w-full px-3 py-2 border rounded-xl font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-amber-800 mb-1">Opening / Past Payable (Rs.)</label>
                        <input type="number" min="0" step="0.01" name="opening_balance" value="0" class="w-full px-3 py-2 border border-amber-300 bg-amber-50/40 rounded-xl font-mono font-bold text-amber-900">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border rounded-xl">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Business Address</label>
                    <textarea name="address" rows="2" class="w-full px-3 py-2 border rounded-xl"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="addSupplierModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Admin Past / Migrated Supplier Balance Adjustment Modal -->
    <div x-show="adjustBalanceModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="adjustBalanceModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-scale-balanced text-amber-600"></i>
                    <span>Adjust Supplier Past / Payable Balance</span>
                </h3>
                <button @click="adjustBalanceModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST"
                  :action="'/suppliers/' + adjustSupplier.id + '/adjust-balance'"
                  @submit="confirmBalanceAdjustment($event, adjustSupplier.name, 'Supplier')"
                  class="space-y-3.5 mt-4 text-xs">
                @csrf
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Supplier</div>
                        <div class="font-bold text-sm text-slate-900" x-text="adjustSupplier.name"></div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-bold uppercase text-slate-400">Current Payable (AP)</div>
                        <div class="font-mono font-black text-sm text-rose-600" x-text="'Rs. ' + parseFloat(adjustSupplier.current_balance || 0).toFixed(2)"></div>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Adjustment Type *</label>
                    <select name="adjustment_mode" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-bold text-slate-800">
                        <option value="set_exact">Set Exact Payable Balance (e.g. Migrated Opening Balance)</option>
                        <option value="increase_payable">Add / Increase Past Payable Balance (+)</option>
                        <option value="decrease_payable">Reduce / Deduct Payable Balance (-)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="0" name="amount" :value="adjustSupplier.current_balance" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-sm font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Adjustment Reason / Migration Notes *</label>
                    <input type="text" name="notes" required value="Opening payable balance migrated from previous system"
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="adjustBalanceModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-sm cursor-pointer">
                        Confirm & Proceed
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
