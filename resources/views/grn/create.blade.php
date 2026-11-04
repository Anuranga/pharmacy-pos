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
                            <th class="py-2.5 px-2" style="width: 21%;">Drug / Medication *</th>
                            <th class="py-2.5 px-2" style="width: 14%;">UOM / Pack Size *</th>
                            <th class="py-2.5 px-2" style="width: 11%;">Batch No *</th>
                            <th class="py-2.5 px-2" style="width: 11%;">Expiry Date *</th>
                            <th class="py-2.5 px-2 text-center" style="width: 9%;">Recv Qty *</th>
                            <th class="py-2.5 px-2 text-center" style="width: 8%;">Bonus Qty</th>
                            <th class="py-2.5 px-2 text-right" style="width: 11%;">Cost (Rs.) *</th>
                            <th class="py-2.5 px-2 text-right" style="width: 11%;">Retail (Rs.) *</th>
                            <th class="py-2.5 px-2 text-right" style="width: 10%;">Subtotal (Rs.)</th>
                            <th class="py-2.5 px-1 text-center" style="width: 4%;"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="hover:bg-slate-50/60 transition-colors align-top">
                                <!-- Drug Select -->
                                <td class="py-2.5 px-2">
                                    <select :name="'items[' + index + '][product_id]'" 
                                            x-model="line.product_id"
                                            @change="onProductSelect(index)"
                                            required 
                                            class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                                        <option value="">-- Select Drug --</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}"
                                                data-cost="{{ $p->unit_cost_price }}"
                                                data-retail="{{ $p->unit_retail_price }}"
                                                data-uom="{{ $p->base_uom ?? 'Tablet' }}"
                                                data-pack="{{ $p->units_per_pack ?? 1 }}">
                                            {{ $p->brand_name }} ({{ $p->dosage_strength }})
                                        </option>
                                        @endforeach
                                    </select>
                                </td>

                                <!-- UOM Mode (Unit/Tablet vs Pack) & Pack Size -->
                                <td class="py-2.5 px-2">
                                    <select :name="'items[' + index + '][purchase_uom]'"
                                            x-model="line.purchase_uom"
                                            @change="onUomChange(index)"
                                            class="w-full px-2 py-1.5 border rounded-lg text-xs font-bold focus:ring-1 focus:ring-emerald-500"
                                            :class="line.purchase_uom === 'pack' ? 'bg-indigo-50 border-indigo-300 text-indigo-800' : 'bg-white border-slate-300 text-slate-700'">
                                        <option value="unit" x-text="(line.base_uom || 'Tablet') + ' (Single)'"></option>
                                        <option value="pack" x-text="'Pack → ' + (line.base_uom || 'Tablets')"></option>
                                    </select>

                                    <template x-if="line.purchase_uom === 'pack'">
                                        <div class="mt-1.5 flex items-center gap-1 bg-indigo-50/80 px-2 py-1 rounded-lg border border-indigo-200 text-[10px]">
                                            <span class="font-semibold text-indigo-700 whitespace-nowrap">1 Pack =</span>
                                            <input type="number"
                                                   min="1"
                                                   :name="'items[' + index + '][units_per_pack]'"
                                                   x-model.number="line.units_per_pack"
                                                   @input="onPackSizeChange(index)"
                                                   required
                                                   class="w-14 px-1 py-0.5 bg-white border border-indigo-300 rounded text-center font-mono font-bold text-indigo-900 text-[11px]">
                                            <span class="font-semibold text-indigo-700 truncate" x-text="line.base_uom || 'Tab'"></span>
                                        </div>
                                    </template>
                                    <template x-if="line.purchase_uom !== 'pack'">
                                        <input type="hidden" :name="'items[' + index + '][units_per_pack]'" value="1">
                                    </template>
                                </td>

                                <!-- Batch Number -->
                                <td class="py-2.5 px-2">
                                    <input type="text" :name="'items[' + index + '][batch_number]'" 
                                           x-model="line.batch_number" required placeholder="e.g. B-1092"
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg font-mono text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>

                                <!-- Expiry Date -->
                                <td class="py-2.5 px-2">
                                    <input type="date" :name="'items[' + index + '][expiry_date]'" 
                                           x-model="line.expiry_date" required min="{{ date('Y-m-d') }}"
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500">
                                </td>

                                <!-- Recv Qty -->
                                <td class="py-2.5 px-2 text-center">
                                    <div class="relative">
                                        <input type="number" min="1" :name="'items[' + index + '][quantity_received]'" 
                                               x-model.number="line.quantity_received" required
                                               class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center font-mono font-bold text-xs focus:ring-1 focus:ring-emerald-500">
                                    </div>
                                    <span class="block text-[10px] text-slate-400 font-semibold mt-0.5"
                                          x-text="line.purchase_uom === 'pack' ? 'Packs' : (line.base_uom || 'Units')"></span>
                                    <template x-if="line.purchase_uom === 'pack'">
                                        <div class="mt-0.5 px-1.5 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono font-bold text-[10px]"
                                             x-text="'= ' + ((parseInt(line.quantity_received) || 0) * (parseInt(line.units_per_pack) || 1)) + ' ' + (line.base_uom || 'Tab')"></div>
                                    </template>
                                </td>

                                <!-- Bonus Qty -->
                                <td class="py-2.5 px-2 text-center">
                                    <input type="number" min="0" :name="'items[' + index + '][free_bonus_qty]'" 
                                           x-model.number="line.free_bonus_qty" placeholder="0"
                                           class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-center font-mono text-xs focus:ring-1 focus:ring-emerald-500">
                                    <span class="block text-[10px] text-slate-400 mt-0.5"
                                          x-text="line.purchase_uom === 'pack' ? 'Bonus Packs' : ('Bonus ' + (line.base_uom || 'Units'))"></span>
                                    <template x-if="line.purchase_uom === 'pack' && (parseInt(line.free_bonus_qty) || 0) > 0">
                                        <div class="mt-0.5 px-1 py-0.5 rounded bg-teal-50 border border-teal-200 text-teal-800 font-mono font-bold text-[10px]"
                                             x-text="'+' + ((parseInt(line.free_bonus_qty) || 0) * (parseInt(line.units_per_pack) || 1)) + ' ' + (line.base_uom || 'Tab')"></div>
                                    </template>
                                </td>

                                <!-- Cost Price -->
                                <td class="py-2.5 px-2 text-right">
                                    <template x-if="line.purchase_uom === 'pack'">
                                        <div class="space-y-1">
                                            <div>
                                                <label class="block text-[9px] text-indigo-600 font-bold uppercase">Per Pack Cost</label>
                                                <input type="number" step="0.01" min="0"
                                                       :name="'items[' + index + '][pack_cost_price]'"
                                                       x-model.number="line.pack_cost_price"
                                                       @input="onPackCostInput(index)"
                                                       class="w-full px-2 py-1 bg-indigo-50/60 border border-indigo-300 rounded-lg text-right font-mono font-bold text-indigo-900 text-xs focus:ring-1 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] text-slate-500 font-semibold" x-text="'Per ' + (line.base_uom || 'Tablet') + ' Cost'"></label>
                                                <input type="number" step="0.01" min="0"
                                                       :name="'items[' + index + '][cost_price]'"
                                                       x-model.number="line.cost_price"
                                                       @input="onUnitCostInput(index)"
                                                       required
                                                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded-lg text-right font-mono text-[11px] text-slate-700 focus:ring-1 focus:ring-emerald-500">
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="line.purchase_uom !== 'pack'">
                                        <div>
                                            <input type="number" step="0.01" min="0" :name="'items[' + index + '][cost_price]'" 
                                                   x-model.number="line.cost_price" required
                                                   class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-right font-mono text-xs focus:ring-1 focus:ring-emerald-500">
                                            <span class="block text-[9px] text-slate-400 mt-0.5" x-text="'per ' + (line.base_uom || 'Unit')"></span>
                                        </div>
                                    </template>
                                </td>

                                <!-- Retail Price -->
                                <td class="py-2.5 px-2 text-right">
                                    <template x-if="line.purchase_uom === 'pack'">
                                        <div class="space-y-1">
                                            <div>
                                                <label class="block text-[9px] text-indigo-600 font-bold uppercase">Per Pack Retail</label>
                                                <input type="number" step="0.01" min="0"
                                                       :name="'items[' + index + '][pack_retail_price]'"
                                                       x-model.number="line.pack_retail_price"
                                                       @input="onPackRetailInput(index)"
                                                       class="w-full px-2 py-1 bg-indigo-50/60 border border-indigo-300 rounded-lg text-right font-mono font-bold text-indigo-900 text-xs focus:ring-1 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] text-emerald-700 font-bold" x-text="'Per ' + (line.base_uom || 'Tablet') + ' Retail'"></label>
                                                <input type="number" step="0.01" min="0"
                                                       :name="'items[' + index + '][retail_price]'"
                                                       x-model.number="line.retail_price"
                                                       @input="onUnitRetailInput(index)"
                                                       required
                                                       class="w-full px-2 py-1 bg-emerald-50/60 border border-emerald-300 rounded-lg text-right font-mono font-bold text-emerald-800 text-[11px] focus:ring-1 focus:ring-emerald-500">
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="line.purchase_uom !== 'pack'">
                                        <div>
                                            <input type="number" step="0.01" min="0" :name="'items[' + index + '][retail_price]'" 
                                                   x-model.number="line.retail_price" required
                                                   class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-right font-mono font-bold text-emerald-700 text-xs focus:ring-1 focus:ring-emerald-500">
                                            <span class="block text-[9px] text-emerald-600 mt-0.5" x-text="'per ' + (line.base_uom || 'Unit')"></span>
                                        </div>
                                    </template>
                                </td>

                                <!-- Line Subtotal -->
                                <td class="py-2.5 px-2 text-right font-mono">
                                    <div class="font-bold text-slate-900 text-xs" x-text="'Rs. ' + getLineSubtotal(line).toFixed(2)"></div>
                                    <div class="text-[10px] text-emerald-700 font-semibold mt-1"
                                         x-text="'Stock: +' + getLineTotalBaseUnits(line) + ' ' + (line.base_uom || 'Tab')"></div>
                                </td>

                                <!-- Remove -->
                                <td class="py-2.5 px-1 text-center">
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
            { product_id: '', purchase_uom: 'unit', base_uom: 'Tablet', units_per_pack: 1, batch_number: '', expiry_date: '', quantity_received: 5, free_bonus_qty: 0, cost_price: 0, retail_price: 0, pack_cost_price: 0, pack_retail_price: 0 },
            { product_id: '', purchase_uom: 'unit', base_uom: 'Tablet', units_per_pack: 1, batch_number: '', expiry_date: '', quantity_received: 5, free_bonus_qty: 0, cost_price: 0, retail_price: 0, pack_cost_price: 0, pack_retail_price: 0 }
        ],

        addLineItem() {
            this.lines.push({
                product_id: '',
                purchase_uom: 'unit',
                base_uom: 'Tablet',
                units_per_pack: 1,
                batch_number: '',
                expiry_date: '',
                quantity_received: 5,
                free_bonus_qty: 0,
                cost_price: 0,
                retail_price: 0,
                pack_cost_price: 0,
                pack_retail_price: 0
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
                const line = this.lines[index];
                const unitCost = parseFloat(opt.dataset.cost) || 0;
                const unitRetail = parseFloat(opt.dataset.retail) || 0;
                const packSize = Math.max(1, parseInt(opt.dataset.pack) || 1);

                line.base_uom = opt.dataset.uom || 'Tablet';
                line.units_per_pack = packSize;
                line.purchase_uom = packSize > 1 ? 'pack' : 'unit';
                line.cost_price = unitCost;
                line.retail_price = unitRetail;
                line.pack_cost_price = parseFloat((unitCost * packSize).toFixed(2));
                line.pack_retail_price = parseFloat((unitRetail * packSize).toFixed(2));
            }
        },

        onUomChange(index) {
            const line = this.lines[index];
            if (line.purchase_uom === 'pack') {
                if (!line.units_per_pack || line.units_per_pack <= 1) {
                    line.units_per_pack = 100;
                }
                line.pack_cost_price = parseFloat(((parseFloat(line.cost_price) || 0) * line.units_per_pack).toFixed(2));
                line.pack_retail_price = parseFloat(((parseFloat(line.retail_price) || 0) * line.units_per_pack).toFixed(2));
            } else {
                line.units_per_pack = 1;
            }
        },

        onPackSizeChange(index) {
            const line = this.lines[index];
            const packSize = Math.max(1, parseInt(line.units_per_pack) || 1);
            if ((parseFloat(line.pack_cost_price) || 0) > 0) {
                line.cost_price = parseFloat(((parseFloat(line.pack_cost_price) || 0) / packSize).toFixed(2));
            }
            if ((parseFloat(line.pack_retail_price) || 0) > 0) {
                line.retail_price = parseFloat(((parseFloat(line.pack_retail_price) || 0) / packSize).toFixed(2));
            }
        },

        onPackCostInput(index) {
            const line = this.lines[index];
            const packSize = Math.max(1, parseInt(line.units_per_pack) || 1);
            line.cost_price = parseFloat(((parseFloat(line.pack_cost_price) || 0) / packSize).toFixed(2));
        },

        onUnitCostInput(index) {
            const line = this.lines[index];
            const packSize = Math.max(1, parseInt(line.units_per_pack) || 1);
            line.pack_cost_price = parseFloat(((parseFloat(line.cost_price) || 0) * packSize).toFixed(2));
        },

        onPackRetailInput(index) {
            const line = this.lines[index];
            const packSize = Math.max(1, parseInt(line.units_per_pack) || 1);
            line.retail_price = parseFloat(((parseFloat(line.pack_retail_price) || 0) / packSize).toFixed(2));
        },

        onUnitRetailInput(index) {
            const line = this.lines[index];
            const packSize = Math.max(1, parseInt(line.units_per_pack) || 1);
            line.pack_retail_price = parseFloat(((parseFloat(line.retail_price) || 0) * packSize).toFixed(2));
        },

        getLineTotalBaseUnits(line) {
            const qty = (parseInt(line.quantity_received) || 0) + (parseInt(line.free_bonus_qty) || 0);
            const mult = line.purchase_uom === 'pack' ? Math.max(1, parseInt(line.units_per_pack) || 1) : 1;
            return qty * mult;
        },

        getLineSubtotal(line) {
            const recv = parseFloat(line.quantity_received) || 0;
            if (line.purchase_uom === 'pack' && (parseInt(line.units_per_pack) || 1) > 1) {
                const packCost = parseFloat(line.pack_cost_price) || ((parseFloat(line.cost_price) || 0) * (parseInt(line.units_per_pack) || 1));
                return recv * packCost;
            }
            return recv * (parseFloat(line.cost_price) || 0);
        },

        get totalUnits() {
            return this.lines.reduce((sum, line) => sum + this.getLineTotalBaseUnits(line), 0);
        },

        get totalAmount() {
            return this.lines.reduce((sum, line) => sum + this.getLineSubtotal(line), 0);
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
