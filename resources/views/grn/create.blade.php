@extends('layouts.app')

@section('title', 'New Goods Received Note (GRN)')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="grnEntry()">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-truck-ramp-box text-emerald-600"></i>
                <span>Procurement: New Goods Received Note (GRN)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Receive drug consignments, register batch numbers & expiry dates, update inventory, and log supplier accounts payable.</p>
        </div>

        <a href="{{ route('grn.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-list-check"></i> GRN Register Log
        </a>
    </div>

    <form method="POST" action="{{ route('grn.store') }}" @submit="validateBeforeSubmit($event)" class="space-y-6">
        @csrf

        <!-- GRN Invoice Metadata Card -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm">
            <h3 class="font-display font-bold text-sm text-slate-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-emerald-600"></i> Consignment & Supplier Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">GRN Number</label>
                    <input type="text" value="{{ $nextGrnNumber }}" readonly class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-xl font-mono font-bold text-slate-600">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Supplier *</label>
                    <select name="supplier_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 font-semibold">
                        <option value="">-- Choose Supplier --</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} (Payable: Rs. {{ number_format($s->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Supplier Invoice No.</label>
                    <input type="text" name="invoice_number" placeholder="e.g. INV-88910" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Invoice / Receipt Date *</label>
                    <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <!-- Dynamic Line Items Card -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-table-list text-emerald-600"></i> Consignment Line Items
                    </h3>
                    <p class="text-[11px] text-slate-500">Fill drug name, batch number, expiry date, quantities and pricing per line.</p>
                </div>

                <button type="button" @click="addLineItem()" 
                        class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-xl text-xs flex items-center gap-1.5 transition-colors">
                    <i class="fa-solid fa-plus"></i> Add Line Item
                </button>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3" style="width: 25%;">Drug / Medication *</th>
                            <th class="py-2.5 px-3" style="width: 14%;">Batch No *</th>
                            <th class="py-2.5 px-3" style="width: 13%;">Expiry Date *</th>
                            <th class="py-2.5 px-3 text-center" style="width: 10%;">Recv Qty *</th>
                            <th class="py-2.5 px-3 text-center" style="width: 9%;">Bonus Qty</th>
                            <th class="py-2.5 px-3 text-right" style="width: 12%;">Cost (Rs.) *</th>
                            <th class="py-2.5 px-3 text-right" style="width: 12%;">Retail (Rs.) *</th>
                            <th class="py-2.5 px-3 text-right" style="width: 12%;">Subtotal (Rs.)</th>
                            <th class="py-2.5 px-2 text-center" style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-2 px-2">
                                    <select :name="'items[' + index + '][product_id]'" 
                                            x-model="line.product_id"
                                            @change="onProductSelect(index)"
                                            required 
                                            class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                                        <option value="">-- Select Drug --</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-cost="{{ $p->unit_cost_price }}" data-retail="{{ $p->unit_retail_price }}">
                                            {{ $p->brand_name }} ({{ $p->dosage_strength }})
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-2">
                                    <input type="text" :name="'items[' + index + '][batch_number]'" 
                                           x-model="line.batch_number" required placeholder="e.g. B-1092"
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="date" :name="'items[' + index + '][expiry_date]'" 
                                           x-model="line.expiry_date" required min="{{ date('Y-m-d') }}"
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <input type="number" min="1" :name="'items[' + index + '][quantity_received]'" 
                                           x-model.number="line.quantity_received" required
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center font-mono font-bold text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <input type="number" min="0" :name="'items[' + index + '][free_bonus_qty]'" 
                                           x-model.number="line.free_bonus_qty" placeholder="0"
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center font-mono text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>
                                <td class="py-2 px-2 text-right">
                                    <input type="number" step="0.01" min="0" :name="'items[' + index + '][cost_price]'" 
                                           x-model.number="line.cost_price" required
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-right font-mono text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>
                                <td class="py-2 px-2 text-right">
                                    <input type="number" step="0.01" min="0" :name="'items[' + index + '][retail_price]'" 
                                           x-model.number="line.retail_price" required
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-right font-mono font-bold text-emerald-700 text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-800">
                                    <span x-text="'Rs. ' + ((line.quantity_received || 0) * (line.cost_price || 0)).toFixed(2)"></span>
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <button type="button" @click="removeLineItem(index)" 
                                            class="text-rose-500 hover:text-rose-700 p-1" title="Remove line">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Financial Summary & Notes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1 text-xs">Receiving Notes / Remarks</label>
                    <textarea name="notes" rows="3" placeholder="Condition of goods, delivery agent, temperature compliance notes..."
                              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="bg-slate-900 text-white p-5 rounded-2xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Total Line Items: <strong class="text-white" x-text="lines.length"></strong></span>
                        <span>Total Units Received: <strong class="text-white font-mono" x-text="totalUnits"></strong></span>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-800 flex items-baseline justify-between">
                        <div>
                            <span class="text-xs uppercase font-bold text-slate-400">Total GRN Amount</span>
                            <div class="text-[11px] text-emerald-400">Appended to Supplier Accounts Payable</div>
                        </div>
                        <div class="text-2xl font-black font-display text-emerald-400 font-mono" x-text="'Rs. ' + totalAmount.toFixed(2)"></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('grn.index') }}" class="px-5 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-check-double"></i>
                    <span>Finalize GRN & Update Inventory</span>
                </button>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
function grnEntry() {
    return {
        lines: [
            { product_id: '', batch_number: '', expiry_date: '', quantity_received: 100, free_bonus_qty: 0, cost_price: 0, retail_price: 0 },
            { product_id: '', batch_number: '', expiry_date: '', quantity_received: 50, free_bonus_qty: 0, cost_price: 0, retail_price: 0 }
        ],

        addLineItem() {
            this.lines.push({
                product_id: '',
                batch_number: '',
                expiry_date: '',
                quantity_received: 10,
                free_bonus_qty: 0,
                cost_price: 0,
                retail_price: 0
            });
        },

        removeLineItem(index) {
            if (this.lines.length > 1) {
                this.lines.splice(index, 1);
            } else {
                Swal.fire({ icon: 'info', title: 'Line Item Required', text: 'At least one line item is required for a GRN.' });
            }
        },

        onProductSelect(index) {
            const select = document.querySelector(`select[name="items[${index}][product_id]"]`);
            if (select && select.selectedIndex > 0) {
                const opt = select.options[select.selectedIndex];
                this.lines[index].cost_price = parseFloat(opt.dataset.cost) || 0;
                this.lines[index].retail_price = parseFloat(opt.dataset.retail) || 0;
            }
        },

        get totalUnits() {
            return this.lines.reduce((sum, line) => sum + (parseInt(line.quantity_received) || 0) + (parseInt(line.free_bonus_qty) || 0), 0);
        },

        get totalAmount() {
            return this.lines.reduce((sum, line) => sum + ((parseFloat(line.quantity_received) || 0) * (parseFloat(line.cost_price) || 0)), 0);
        },

        validateBeforeSubmit(e) {
            if (this.lines.length === 0) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Empty GRN', text: 'Please add at least one drug line item.' });
            }
        }
    };
}
</script>
@endpush
