@extends('layouts.app')

@section('title', 'Customer Bill Return - Sales Return')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5"
     x-data="customerReturnApp()"
     x-init="init()">

    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <a href="{{ route('returns.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Returns Hub
        </a>
        <a href="{{ route('pos.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
            <i class="fa-solid fa-cash-register"></i> Go to POS Counter
        </a>
    </div>

    <!-- Step 1: Search / Select Original POS Bill -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
                <h1 class="text-xl font-extrabold font-display text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left text-emerald-600"></i>
                    <span>Customer Billed Item Return (Sales Return)</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Search by POS Invoice Number (`INV-...`) or select from recent bills to return items, restock good items to shelf, and refund or reduce customer debt.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end text-xs">
            <div class="md:col-span-6">
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Search Invoice No / Customer Name or Phone</label>
                <div class="flex gap-2">
                    <input type="text"
                           x-model="searchQuery"
                           @keydown.enter.prevent="searchInvoice()"
                           placeholder="e.g. INV-2026... or Customer Name..."
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <button type="button"
                            @click="searchInvoice()"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl flex items-center gap-1.5">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Find Bill</span>
                    </button>
                </div>
            </div>

            <div class="md:col-span-6">
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Or Select from Recent Completed POS Bills</label>
                <select @change="if ($event.target.value) loadSaleById($event.target.value)"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- Choose a Recent POS Invoice --</option>
                    @foreach($recentSales as $rs)
                    <option value="{{ $rs->id }}" {{ ($preselectedSale && $preselectedSale['id'] == $rs->id) ? 'selected' : '' }}>
                        {{ $rs->invoice_number }} — {{ $rs->customer->name ?? 'Walk-in' }} (Rs. {{ number_format($rs->grand_total, 2) }} | {{ $rs->created_at->format('d M H:i') }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Step 2: Loaded Invoice Details & Return Line Items Form -->
    <template x-if="sale">
        <form method="POST" action="{{ route('returns.customer.store') }}" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5 text-xs">
            @csrf
            <input type="hidden" name="sale_id" :value="sale.id">

            <!-- Invoice Summary Banner -->
            <div class="p-4 rounded-2xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 font-bold">
                        <i class="fa-solid fa-file-invoice text-lg"></i>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Selected POS Invoice</div>
                        <div class="text-base font-extrabold font-mono text-emerald-400" x-text="sale.invoice_number"></div>
                        <div class="text-[11px] text-slate-300" x-text="'Billed on ' + sale.date + ' | Mode: ' + sale.payment_type.toUpperCase()"></div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400">Customer</div>
                        <div class="font-bold text-sm text-white" x-text="sale.customer_name"></div>
                        <template x-if="sale.customer_id">
                            <div class="text-[11px] text-amber-400 font-mono" x-text="'Current Debt: Rs. ' + parseFloat(sale.customer_debt).toFixed(2)"></div>
                        </template>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] uppercase tracking-wider text-slate-400">Original Bill Total</div>
                        <div class="text-lg font-black font-mono text-white" x-text="'Rs. ' + parseFloat(sale.grand_total).toFixed(2)"></div>
                    </div>
                </div>
            </div>

            <!-- Billed Items Table -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-display font-bold text-sm text-slate-800">
                        Select Billed Items & Quantities to Return
                    </h3>
                    <button type="button"
                            @click="returnAllRemaining()"
                            class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 text-emerald-700 font-bold text-[11px] border border-slate-200">
                        <i class="fa-solid fa-check-double mr-1"></i> Return All Remaining Items
                    </button>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-3">Medication / Batch</th>
                                <th class="py-3 px-3 text-center">Sold Qty</th>
                                <th class="py-3 px-3 text-center">Already Returned</th>
                                <th class="py-3 px-3 text-center">Returnable</th>
                                <th class="py-3 px-3">Return Unit</th>
                                <th class="py-3 px-3 text-center">Return Qty *</th>
                                <th class="py-3 px-3 text-right">Refund Unit Price (Rs.)</th>
                                <th class="py-3 px-3">Stock Condition</th>
                                <th class="py-3 px-3 text-right">Refund Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, idx) in sale.items" :key="item.sale_item_id">
                                <tr :class="item.return_input_qty > 0 ? 'bg-emerald-50/40' : 'hover:bg-slate-50/70'">
                                    <td class="py-3 px-3">
                                        <input type="hidden" :name="`items[${idx}][sale_item_id]`" :value="item.sale_item_id">
                                        <div class="font-bold text-slate-900" x-text="item.brand_name"></div>
                                        <div class="text-[10px] font-mono text-slate-500">
                                            Batch: <span class="font-semibold text-slate-700" x-text="item.batch_number"></span>
                                            <template x-if="item.units_per_pack > 1">
                                                <span class="ml-1 text-indigo-600 font-semibold" x-text="'(1 Pack = ' + item.units_per_pack + ' ' + item.base_uom + ')'"></span>
                                            </template>
                                        </div>
                                    </td>

                                    <td class="py-3 px-3 text-center font-mono font-bold text-slate-700">
                                        <span x-text="item.sold_qty + ' ' + item.base_uom"></span>
                                    </td>

                                    <td class="py-3 px-3 text-center font-mono text-slate-500">
                                        <span x-text="item.already_returned_qty"></span>
                                    </td>

                                    <td class="py-3 px-3 text-center font-mono font-bold"
                                        :class="item.remaining_qty > 0 ? 'text-emerald-700' : 'text-rose-500'">
                                        <span x-text="item.remaining_qty + ' ' + item.base_uom"></span>
                                    </td>

                                    <td class="py-3 px-3">
                                        <select :name="`items[${idx}][return_uom]`"
                                                x-model="item.return_uom"
                                                @change="validateCustomerRowQty(item)"
                                                :disabled="item.remaining_qty <= 0"
                                                class="px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-semibold">
                                            <option value="unit" x-text="item.base_uom + ' (Single)'"></option>
                                            <template x-if="item.units_per_pack > 1 && item.remaining_qty >= item.units_per_pack">
                                                <option value="pack" x-text="'Pack (' + item.units_per_pack + ' ' + item.base_uom + ')'"></option>
                                            </template>
                                        </select>
                                    </td>

                                    <td class="py-3 px-3 text-center">
                                        <input type="number"
                                               min="0"
                                               :max="item.return_uom === 'pack' ? Math.floor(item.remaining_qty / item.units_per_pack) : item.remaining_qty"
                                               :name="`items[${idx}][return_qty]`"
                                               x-model.number="item.return_input_qty"
                                               @input="validateCustomerRowQty(item)"
                                               :disabled="item.remaining_qty <= 0"
                                               class="w-20 px-2 py-1 text-center font-mono font-bold bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                                        <template x-if="item.return_uom === 'pack' && item.return_input_qty > 0">
                                            <div class="text-[10px] font-mono text-indigo-700 font-bold mt-0.5"
                                                 x-text="'= ' + getRowBaseReturnQty(item) + ' ' + item.base_uom"></div>
                                        </template>
                                    </td>

                                    <td class="py-3 px-3 text-right">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               :name="`items[${idx}][unit_price]`"
                                               x-model.number="item.unit_price"
                                               class="w-24 px-2 py-1 text-right font-mono font-bold bg-white border border-slate-300 rounded-lg">
                                        <div class="text-[9px] text-slate-400 font-mono" x-text="'per ' + item.base_uom"></div>
                                    </td>

                                    <td class="py-3 px-3">
                                        <select :name="`items[${idx}][restock_status]`"
                                                x-model="item.restock_status"
                                                class="px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-semibold">
                                            <option value="restocked">Restock to Shelf (Good)</option>
                                            <option value="discarded">Damaged / Discard (No Stock)</option>
                                        </select>
                                    </td>

                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                        Rs. <span x-text="getRowSubtotal(item).toFixed(2)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Step 3: Refund Mode, Reason & Total Summary -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-2">
                <div class="md:col-span-7 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Refund / Settlement Method *</label>
                            <select name="refund_method" x-model="refundMethod" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold">
                                <option value="cash">Cash Refund (Pay from Drawer)</option>
                                <option value="credit_reduction" :disabled="!sale.customer_id">Reduce Customer Debt / Credit Note</option>
                                <option value="exchange">Item Exchange / Store Credit</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Return Reason *</label>
                            <select name="reason" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold">
                                <option value="Doctor Changed Prescription">Doctor Changed Prescription</option>
                                <option value="Customer Changed Mind / Excess">Customer Changed Mind / Excess Qty</option>
                                <option value="Wrong Medication Dispensed">Wrong Medication Dispensed</option>
                                <option value="Damaged / Defective Packaging">Damaged / Defective Packaging</option>
                                <option value="Adverse Reaction">Adverse Reaction</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Return Notes / Remarks</label>
                        <textarea name="notes" rows="2" placeholder="Optional notes regarding the customer return..."
                                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl"></textarea>
                    </div>
                </div>

                <div class="md:col-span-5 bg-slate-900 text-white rounded-2xl p-5 flex flex-col justify-between space-y-4">
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>Total Base Units Returning:</span>
                            <span class="font-mono font-bold text-white" x-text="totalReturningUnits + ' units'"></span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>Units Restocking to Inventory:</span>
                            <span class="font-mono font-bold text-emerald-400" x-text="totalRestockingUnits + ' units'"></span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex items-baseline justify-between">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Refund Value</span>
                            <div class="text-[10px] text-emerald-400 font-mono" x-text="refundMethod === 'credit_reduction' ? 'Deducted from Customer Debt' : 'Customer Refund (LKR)'"></div>
                        </div>
                        <div class="text-2xl font-black font-display text-emerald-400 font-mono"
                             x-text="'Rs. ' + totalRefundAmount.toFixed(2)"></div>
                    </div>

                    <button type="submit"
                            :disabled="totalReturningUnits <= 0"
                            class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Confirm Customer Return & Update Stock</span>
                    </button>
                </div>
            </div>
        </form>
    </template>

</div>

@push('scripts')
<script>
function customerReturnApp() {
    return {
        searchQuery: '',
        sale: @json($preselectedSale),
        refundMethod: 'cash',

        init() {
            if (this.sale && this.sale.payment_type === 'credit_lending' && this.sale.customer_id) {
                this.refundMethod = 'credit_reduction';
            }
        },

        async searchInvoice() {
            if (!this.searchQuery.trim()) return;
            try {
                const res = await fetch(`{{ route('returns.customer.lookup') }}?q=${encodeURIComponent(this.searchQuery.trim())}`);
                const data = await res.json();
                if (data.success) {
                    this.sale = data.sale;
                    this.refundMethod = (this.sale.payment_type === 'credit_lending' && this.sale.customer_id) ? 'credit_reduction' : 'cash';
                } else {
                    Swal.fire({ icon: 'warning', title: 'Invoice Not Found', text: data.message });
                }
            } catch (e) {
                console.error(e);
            }
        },

        async loadSaleById(saleId) {
            try {
                const res = await fetch(`{{ route('returns.customer.lookup') }}?sale_id=${encodeURIComponent(saleId)}`);
                const data = await res.json();
                if (data.success) {
                    this.sale = data.sale;
                    this.refundMethod = (this.sale.payment_type === 'credit_lending' && this.sale.customer_id) ? 'credit_reduction' : 'cash';
                }
            } catch (e) {
                console.error(e);
            }
        },

        getRowBaseReturnQty(item) {
            const qty = Math.max(0, parseInt(item.return_input_qty) || 0);
            const packSize = Math.max(1, parseInt(item.units_per_pack) || 1);
            return (item.return_uom === 'pack' && packSize > 1) ? (qty * packSize) : qty;
        },

        validateCustomerRowQty(item) {
            const packSize = Math.max(1, parseInt(item.units_per_pack) || 1);
            const maxInput = item.return_uom === 'pack'
                ? Math.floor(item.remaining_qty / packSize)
                : item.remaining_qty;
            if ((parseInt(item.return_input_qty) || 0) > maxInput) {
                item.return_input_qty = maxInput;
            }
            if ((parseInt(item.return_input_qty) || 0) < 0) {
                item.return_input_qty = 0;
            }
        },

        returnAllRemaining() {
            if (!this.sale) return;
            this.sale.items.forEach(item => {
                item.return_uom = 'unit';
                item.return_input_qty = item.remaining_qty;
            });
        },

        getRowSubtotal(item) {
            return this.getRowBaseReturnQty(item) * (parseFloat(item.unit_price) || 0);
        },

        get totalReturningUnits() {
            if (!this.sale) return 0;
            return this.sale.items.reduce((sum, item) => sum + this.getRowBaseReturnQty(item), 0);
        },

        get totalRestockingUnits() {
            if (!this.sale) return 0;
            return this.sale.items.reduce((sum, item) => {
                return sum + (item.restock_status === 'restocked' ? this.getRowBaseReturnQty(item) : 0);
            }, 0);
        },

        get totalRefundAmount() {
            if (!this.sale) return 0;
            return this.sale.items.reduce((sum, item) => sum + this.getRowSubtotal(item), 0);
        }
    };
}
</script>
@endpush
@endsection
