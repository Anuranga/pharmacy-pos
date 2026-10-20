@extends('layouts.app')

@section('title', $product->brand_name . ' - Product Master')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
     x-data="{ adjustModal: false, selectedBatchId: '', batchNumber: '', currentStock: 0, newQuantity: 0, adjustReason: 'Audit Recount', adjustNotes: '' }">
    
    <!-- Top Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('master.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Drug Catalog
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('master.products.edit', $product->id) }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Medication</span>
            </a>
            <form method="POST" action="{{ route('master.products.destroy', $product->id) }}" onsubmit="return confirm('Are you sure you want to archive this drug?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-xl border border-rose-300 text-rose-600 hover:bg-rose-50 text-xs font-semibold">
                    <i class="fa-solid fa-trash mr-1"></i> Archive Drug
                </button>
            </form>
        </div>
    </div>

    <!-- Product Profile Header -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">SKU: {{ $product->barcode_sku }}</span>
                    @if($product->requires_prescription)
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-700 uppercase">Prescription (Rx)</span>
                    @endif
                    @if($product->is_controlled)
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-purple-100 text-purple-700 uppercase">Controlled Drug</span>
                    @endif
                </div>

                <h1 class="text-3xl font-extrabold font-display text-slate-900 tracking-tight">{{ $product->brand_name }}</h1>
                <p class="text-sm font-medium text-slate-600 italic mt-0.5">Composition: {{ $product->generic_name ?? 'Not specified' }}</p>
                <p class="text-xs text-slate-500 mt-1">
                    Category: <span class="font-semibold text-slate-700">{{ $product->category->name ?? 'General' }}</span> | 
                    Dosage: <span class="font-semibold text-slate-700">{{ $product->dosage_strength ?? 'N/A' }}</span> |
                    Form: <span class="font-semibold text-slate-700">{{ ucfirst($product->item_type) }}</span> ({{ $product->base_uom }})
                </p>
            </div>

            <!-- Price & Live Stock Summary Cards -->
            <div class="flex items-center gap-3">
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-right">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Cost Price</span>
                    <div class="text-sm font-mono font-bold text-slate-700">Rs. {{ number_format($product->unit_cost_price, 2) }}</div>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-right">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase">Retail Price</span>
                    <div class="text-lg font-mono font-black text-emerald-800">Rs. {{ number_format($product->unit_retail_price, 2) }}</div>
                </div>
                <div class="bg-teal-50 border border-teal-200 rounded-2xl p-3 text-right">
                    <span class="text-[10px] font-bold text-teal-600 uppercase">Total Stock</span>
                    <div class="text-lg font-mono font-black text-teal-800">{{ $product->total_stock }} {{ $product->base_uom }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Batches & Inventory Adjustment Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-cubes text-emerald-600"></i>
                    <span>Inventory Batches (FEFO Tracked)</span>
                </h3>
                <p class="text-xs text-slate-500">Batches are sorted strictly by earliest expiry date. Counter sales automatically deduct from the earliest unexpired batch.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Batch Number</th>
                        <th class="py-3 px-4">Expiry Date</th>
                        <th class="py-3 px-4">Shelf Status</th>
                        <th class="py-3 px-4 text-right">Cost Price (Rs.)</th>
                        <th class="py-3 px-4 text-right">Selling Price (Rs.)</th>
                        <th class="py-3 px-4 text-center">Batch Stock</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4 text-right">Stock Adjustment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($product->batches()->orderBy('expiry_date', 'asc')->get() as $batch)
                    @php $status = $batch->expiry_status; @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">
                            {{ $batch->batch_number }}
                        </td>
                        <td class="py-3 px-4 font-mono">
                            {{ $batch->expiry_date->format('d M Y') }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $status['class'] }}">
                                {{ $status['status'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono">
                            {{ number_format($batch->purchase_cost, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            {{ number_format($batch->selling_price, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-sm">
                            <span class="{{ $batch->stock_quantity <= 0 ? 'text-rose-500' : 'text-emerald-700' }}">
                                {{ $batch->stock_quantity }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            {{ $batch->supplier->name ?? 'Direct Purchase' }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <button type="button"
                                    @click="adjustModal = true; selectedBatchId = {{ $batch->id }}; batchNumber = '{{ $batch->batch_number }}'; currentStock = {{ $batch->stock_quantity }}; newQuantity = {{ $batch->stock_quantity }};"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold shadow-2xs transition-colors">
                                <i class="fa-solid fa-sliders text-emerald-600 mr-1"></i> Adjust
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-400">
                            No batches registered for this medicine yet. Add stock via GRN or quick stock entry.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock Adjustment Audit History Table -->
    @if($product->stockAdjustments->isNotEmpty())
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-5">
        <h4 class="font-display font-bold text-sm text-slate-900 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-slate-500"></i> Stock Adjustment Audit Trail
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-2 px-3">Date</th>
                        <th class="py-2 px-3">Adjusted By</th>
                        <th class="py-2 px-3">Batch</th>
                        <th class="py-2 px-3 text-center">Previous Qty</th>
                        <th class="py-2 px-3 text-center">New Qty</th>
                        <th class="py-2 px-3">Reason</th>
                        <th class="py-2 px-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($product->stockAdjustments()->latest()->limit(10)->get() as $adj)
                    <tr>
                        <td class="py-2 px-3 font-mono text-slate-500">{{ $adj->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-2 px-3 font-semibold text-slate-800">{{ $adj->adjuster->name ?? 'Admin' }}</td>
                        <td class="py-2 px-3 font-mono">{{ $adj->batch->batch_number ?? 'N/A' }}</td>
                        <td class="py-2 px-3 text-center font-mono">{{ $adj->previous_quantity }}</td>
                        <td class="py-2 px-3 text-center font-mono font-bold text-emerald-700">{{ $adj->new_quantity }}</td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $adj->reason }}</span>
                        </td>
                        <td class="py-2 px-3 text-slate-500 italic">{{ $adj->notes ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- MODAL: Admin Stock Adjustment with Mandatory Reason Logging -->
    <div x-show="adjustModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200" @click.outside="adjustModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-emerald-600"></i>
                    <span>Inline Stock Adjustment (Admin)</span>
                </h3>
                <button @click="adjustModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('master.adjust-stock') }}" class="space-y-3 mt-3 text-xs">
                @csrf
                <input type="hidden" name="batch_id" :value="selectedBatchId">

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="text-[11px] text-slate-500">Selected Batch:</div>
                    <div class="font-bold font-mono text-sm text-slate-900" x-text="batchNumber"></div>
                    <div class="text-[11px] text-slate-500 mt-1">Current Stock on Hand: <strong class="text-slate-800" x-text="currentStock + ' units'"></strong></div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">New Physical Stock Count *</label>
                    <input type="number" min="0" name="new_quantity" x-model.number="newQuantity" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl font-mono text-sm font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Mandatory Adjustment Reason *</label>
                    <select name="reason" x-model="adjustReason" required class="w-full px-3 py-2 border border-slate-300 rounded-xl font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="Damage">Damage (Water/Physical)</option>
                        <option value="Expiry">Expiry (Write-Off)</option>
                        <option value="Breakage">Breakage / Spillage</option>
                        <option value="Audit Recount">Audit Recount / Stocktaking</option>
                        <option value="Other">Other Adjustment</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Audit Notes / Explanation</label>
                    <textarea name="notes" x-model="adjustNotes" rows="2" placeholder="Describe the reason for adjustment..."
                              class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="adjustModal = false" class="px-3 py-1.5 border rounded-lg text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
