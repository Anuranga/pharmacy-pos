@extends('layouts.app')

@section('title', 'Shop GRN Return - Supplier Debit Note')

@section('content')
@php
    $productsJson = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'brand_name' => $p->brand_name,
            'base_uom' => $p->base_uom ?? 'Tablet',
            'units_per_pack' => max(1, (int) ($p->units_per_pack ?? 1)),
            'unit_cost_price' => (float) $p->unit_cost_price,
            'batches' => $p->batches->map(fn($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'expiry_date' => $b->expiry_date ? $b->expiry_date->format('Y-m-d') : null,
                'stock_quantity' => (int) $b->stock_quantity,
                'purchase_cost' => (float) $b->purchase_cost,
                'supplier_id' => $b->supplier_id,
            ])->values(),
        ];
    })->values();
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5"
     x-data="supplierReturnApp()"
     x-init="init()">

    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <a href="{{ route('returns.index', ['tab' => 'supplier']) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Returns Hub
        </a>
        <a href="{{ route('grn.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            <i class="fa-solid fa-truck-ramp-box"></i> View GRN Register
        </a>
    </div>

    <!-- Header & Mode Selection -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h1 class="text-xl font-extrabold font-display text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-truck-arrow-right fa-flip-horizontal text-indigo-600"></i>
                    <span>Shop GRN Return to Supplier (Purchase Return / Debit Note)</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Return received GRN items or expiring/damaged batches to the supplier, automatically deducting inventory stock and reducing Supplier Accounts Payable.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end text-xs">
            <div class="md:col-span-7">
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Option 1: Load Items from a Received GRN Consignment
                </label>
                <select x-model="selectedGrnId"
                        @change="onGrnChange()"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Select GRN to Auto-Load Items (Or Leave Blank for Direct Batch Return) --</option>
                    @foreach($recentGrns as $g)
                    <option value="{{ $g->id }}">
                        {{ $g->grn_number }} — {{ $g->supplier->name ?? 'Supplier' }} (Inv: {{ $g->invoice_number ?: 'N/A' }} | Rs. {{ number_format($g->total_amount, 2) }} | {{ $g->invoice_date ? $g->invoice_date->format('d M Y') : '' }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-5 flex items-center justify-end gap-2">
                <button type="button"
                        @click="addManualRow()"
                        class="px-4 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold text-xs flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span>+ Add Direct Batch Line</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Supplier Return Form -->
    <form method="POST" action="{{ route('returns.supplier.store') }}" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-5 text-xs">
        @csrf
        <input type="hidden" name="grn_id" :value="selectedGrnId || ''">

        <!-- Supplier & Settlement Header -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Supplier *</label>
                <select name="supplier_id" x-model="supplierId" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-semibold">
                    <option value="">-- Select Supplier --</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">
                        {{ $s->name }} (Payable: Rs. {{ number_format($s->current_balance, 2) }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Supplier Settlement Mode *</label>
                <select name="settlement_method" x-model="settlementMethod" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-semibold">
                    <option value="payable_deduction">Deduct from Supplier Payable (Debit Note)</option>
                    <option value="cash_refund">Cash / Bank Refund from Supplier</option>
                    <option value="replacement">Replacement Stock Pending</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Return Reason *</label>
                <select name="reason" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl font-semibold">
                    <option value="Near Expiry / Expired Stock">Near Expiry / Expired Stock</option>
                    <option value="Damaged in Transit / Defective">Damaged in Transit / Defective</option>
                    <option value="Wrong Item / Batch Supplied">Wrong Item / Batch Supplied</option>
                    <option value="Excess Consignment Return">Excess Consignment Return</option>
                    <option value="Manufacturer Recall">Manufacturer Recall</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>

        <!-- Return Line Items Table -->
        <div class="overflow-x-auto border border-slate-200 rounded-2xl">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3">Drug / Medication *</th>
                        <th class="py-3 px-3">Batch No *</th>
                        <th class="py-3 px-3 text-center">Current Stock</th>
                        <th class="py-3 px-3">Return UOM *</th>
                        <th class="py-3 px-3 text-center">Units / Pack</th>
                        <th class="py-3 px-3 text-center">Return Qty *</th>
                        <th class="py-3 px-3 text-right">Unit Cost (Rs.) *</th>
                        <th class="py-3 px-3 text-right">Return Subtotal</th>
                        <th class="py-3 px-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(row, idx) in items" :key="row.row_key">
                        <tr :class="row.return_input_qty > 0 ? 'bg-indigo-50/40' : 'hover:bg-slate-50/70'">
                            <td class="py-3 px-3 min-w-[210px]">
                                <input type="hidden" :name="`items[${idx}][grn_item_id]`" :value="row.grn_item_id || ''">
                                <input type="hidden" :name="`items[${idx}][batch_id]`" :value="row.batch_id || ''">

                                <template x-if="row.grn_item_id">
                                    <div>
                                        <input type="hidden" :name="`items[${idx}][product_id]`" :value="row.product_id">
                                        <div class="font-bold text-slate-900" x-text="row.brand_name"></div>
                                        <div class="text-[10px] font-mono text-slate-500"
                                             x-text="'GRN Recv: ' + row.received_base_qty + ' ' + row.base_uom + ' (Returnable: ' + row.remaining_returnable_qty + ')'"></div>
                                    </div>
                                </template>

                                <template x-if="!row.grn_item_id">
                                    <select :name="`items[${idx}][product_id]`"
                                            x-model="row.product_id"
                                            @change="onManualProductChange(row)"
                                            required
                                            class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold">
                                        <option value="">-- Select Drug --</option>
                                        <template x-for="p in catalogProducts" :key="p.id">
                                            <option :value="p.id" x-text="p.brand_name + ' (' + p.base_uom + ')'"></option>
                                        </template>
                                    </select>
                                </template>
                            </td>

                            <td class="py-3 px-3 min-w-[140px]">
                                <template x-if="row.grn_item_id">
                                    <div>
                                        <input type="hidden" :name="`items[${idx}][batch_number]`" :value="row.batch_number">
                                        <span class="font-mono font-bold text-slate-800" x-text="row.batch_number"></span>
                                        <span class="text-[10px] text-slate-400 block font-mono" x-show="row.expiry_date" x-text="'Exp: ' + row.expiry_date"></span>
                                    </div>
                                </template>

                                <template x-if="!row.grn_item_id">
                                    <div>
                                        <template x-if="getProductBatches(row.product_id).length > 0">
                                            <select @change="onManualBatchSelect(row, $event.target.value)"
                                                    class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-xs">
                                                <template x-for="b in getProductBatches(row.product_id)" :key="b.id">
                                                    <option :value="b.id" :selected="b.id == row.batch_id"
                                                            x-text="b.batch_number + ' (Stock: ' + b.stock_quantity + ' | Rs.' + b.purchase_cost + ')'"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <input type="hidden" :name="`items[${idx}][batch_number]`" :value="row.batch_number">
                                    </div>
                                </template>
                            </td>

                            <td class="py-3 px-3 text-center font-mono font-bold"
                                :class="row.current_batch_stock > 0 ? 'text-emerald-700' : 'text-rose-500'">
                                <span x-text="row.current_batch_stock + ' ' + (row.base_uom || 'Units')"></span>
                            </td>

                            <td class="py-3 px-3">
                                <select :name="`items[${idx}][return_uom]`"
                                        x-model="row.return_uom"
                                        class="px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold">
                                    <option value="unit" x-text="(row.base_uom || 'Tablet') + ' (Single)'"></option>
                                    <option value="pack">Pack</option>
                                </select>
                            </td>

                            <td class="py-3 px-3 text-center">
                                <input type="number"
                                       min="1"
                                       :name="`items[${idx}][units_per_pack]`"
                                       x-model.number="row.units_per_pack"
                                       :readonly="row.return_uom !== 'pack'"
                                       :class="row.return_uom === 'pack' ? 'bg-indigo-50 border-indigo-300 text-indigo-900 font-bold' : 'bg-slate-100 border-slate-200 text-slate-400'"
                                       class="w-16 px-2 py-1 text-center font-mono rounded-lg border">
                            </td>

                            <td class="py-3 px-3 text-center">
                                <input type="number"
                                       min="0"
                                       :name="`items[${idx}][return_qty]`"
                                       x-model.number="row.return_input_qty"
                                       class="w-20 px-2 py-1 text-center font-mono font-bold bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <template x-if="row.return_uom === 'pack' && row.return_input_qty > 0">
                                    <div class="text-[10px] font-mono font-bold text-indigo-700 mt-0.5"
                                         x-text="'= ' + getRowBaseQty(row) + ' ' + (row.base_uom || 'Tab')"></div>
                                </template>
                            </td>

                            <td class="py-3 px-3 text-right">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="`items[${idx}][unit_cost]`"
                                       x-model.number="row.unit_cost"
                                       class="w-24 px-2 py-1 text-right font-mono font-bold bg-white border border-slate-300 rounded-lg">
                                <div class="text-[9px] text-slate-400 font-mono" x-text="'per ' + (row.base_uom || 'Unit')"></div>
                            </td>

                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                Rs. <span x-text="getRowSubtotal(row).toFixed(2)"></span>
                            </td>

                            <td class="py-3 px-2 text-center">
                                <button type="button"
                                        @click="removeRow(idx)"
                                        x-show="items.length > 1"
                                        class="text-rose-400 hover:text-rose-600 p-1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Notes & Total Debit Note Box -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-2">
            <div class="md:col-span-7">
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Return / Debit Note Remarks</label>
                <textarea name="notes" rows="3" placeholder="Delivery agent, batch return authorization, credit note reference..."
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl"></textarea>
            </div>

            <div class="md:col-span-5 bg-slate-900 text-white rounded-2xl p-5 flex flex-col justify-between space-y-4">
                <div class="flex justify-between text-xs text-slate-400">
                    <span>Total Base Units Deducted from Stock:</span>
                    <span class="font-mono font-bold text-white" x-text="totalBaseUnits + ' units'"></span>
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-baseline justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Return Value</span>
                        <div class="text-[10px] text-indigo-400 font-mono" x-text="settlementMethod === 'payable_deduction' ? 'Deducted from Supplier Payable' : 'Supplier Return Total'"></div>
                    </div>
                    <div class="text-2xl font-black font-display text-indigo-400 font-mono"
                         x-text="'Rs. ' + totalReturnAmount.toFixed(2)"></div>
                </div>

                <button type="submit"
                        :disabled="totalBaseUnits <= 0 || !supplierId"
                        class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Confirm Shop GRN Return & Deduct Stock</span>
                </button>
            </div>
        </div>
    </form>

</div>

@push('scripts')
<script>
function supplierReturnApp() {
    return {
        catalogProducts: @json($productsJson),
        preselectedGrn: @json($preselectedGrn),
        selectedGrnId: '{{ $preselectedGrn['id'] ?? '' }}',
        supplierId: '{{ $preselectedGrn['supplier_id'] ?? '' }}',
        settlementMethod: 'payable_deduction',
        items: [],

        init() {
            if (this.preselectedGrn) {
                this.applyGrnData(this.preselectedGrn);
            } else {
                this.addManualRow();
            }
        },

        async onGrnChange() {
            if (!this.selectedGrnId) {
                this.items = [];
                this.addManualRow();
                return;
            }
            try {
                const res = await fetch(`{{ route('returns.supplier.lookup-grn') }}?grn_id=${encodeURIComponent(this.selectedGrnId)}`);
                const data = await res.json();
                if (data.success) {
                    this.applyGrnData(data.grn);
                }
            } catch (e) {
                console.error(e);
            }
        },

        applyGrnData(grn) {
            this.supplierId = grn.supplier_id;
            this.items = grn.items.map((it, i) => ({
                row_key: 'grn-' + it.grn_item_id + '-' + i,
                grn_item_id: it.grn_item_id,
                product_id: it.product_id,
                brand_name: it.brand_name,
                base_uom: it.base_uom || 'Tablet',
                units_per_pack: Math.max(1, parseInt(it.units_per_pack) || 1),
                batch_id: it.batch_id,
                batch_number: it.batch_number,
                expiry_date: it.expiry_date,
                received_base_qty: it.received_base_qty,
                remaining_returnable_qty: it.remaining_returnable_qty,
                current_batch_stock: it.current_batch_stock,
                unit_cost: parseFloat(it.unit_cost) || 0,
                return_uom: it.return_uom || 'unit',
                return_input_qty: 0,
            }));
        },

        addManualRow() {
            this.items.push({
                row_key: 'manual-' + Date.now() + '-' + Math.random().toString(36).slice(2, 6),
                grn_item_id: null,
                product_id: '',
                brand_name: '',
                base_uom: 'Tablet',
                units_per_pack: 1,
                batch_id: null,
                batch_number: 'BATCH-01',
                expiry_date: null,
                received_base_qty: 0,
                remaining_returnable_qty: 0,
                current_batch_stock: 0,
                unit_cost: 0,
                return_uom: 'unit',
                return_input_qty: 1,
            });
        },

        removeRow(idx) {
            this.items.splice(idx, 1);
        },

        getProductBatches(productId) {
            const p = this.catalogProducts.find(x => x.id == productId);
            return p ? p.batches : [];
        },

        onManualProductChange(row) {
            const p = this.catalogProducts.find(x => x.id == row.product_id);
            if (!p) return;
            row.brand_name = p.brand_name;
            row.base_uom = p.base_uom || 'Tablet';
            row.units_per_pack = Math.max(1, parseInt(p.units_per_pack) || 1);
            row.unit_cost = parseFloat(p.unit_cost_price) || 0;

            if (p.batches && p.batches.length > 0) {
                const b = p.batches[0];
                row.batch_id = b.id;
                row.batch_number = b.batch_number;
                row.current_batch_stock = b.stock_quantity;
                row.unit_cost = parseFloat(b.purchase_cost) || row.unit_cost;
            }
        },

        onManualBatchSelect(row, batchId) {
            const batches = this.getProductBatches(row.product_id);
            const b = batches.find(x => x.id == batchId);
            if (!b) return;
            row.batch_id = b.id;
            row.batch_number = b.batch_number;
            row.current_batch_stock = b.stock_quantity;
            row.unit_cost = parseFloat(b.purchase_cost) || row.unit_cost;
        },

        getRowBaseQty(row) {
            const qty = Math.max(0, parseInt(row.return_input_qty) || 0);
            const packSize = Math.max(1, parseInt(row.units_per_pack) || 1);
            return row.return_uom === 'pack' ? (qty * packSize) : qty;
        },

        getRowSubtotal(row) {
            return this.getRowBaseQty(row) * (parseFloat(row.unit_cost) || 0);
        },

        get totalBaseUnits() {
            return this.items.reduce((sum, r) => sum + this.getRowBaseQty(r), 0);
        },

        get totalReturnAmount() {
            return this.items.reduce((sum, r) => sum + this.getRowSubtotal(r), 0);
        }
    };
}
</script>
@endpush
@endsection
