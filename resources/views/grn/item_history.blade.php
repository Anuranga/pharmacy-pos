@extends('layouts.app')

@section('title', 'Item-Wise GRN History Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-teal-600"></i>
                <span>Item-Wise GRN Procurement History</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Select any medication to view historical log of all suppliers who provided the drug, cost price fluctuations, batch numbers, and receipt dates.</p>
        </div>

        <a href="{{ route('grn.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to GRN Register
        </a>
    </div>

    <!-- Drug Selector Dropdown Card -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-sm">
        <form method="GET" action="{{ route('grn.item-history') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end text-xs">
            <div class="md:col-span-9">
                <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Drug / Medication *</label>
                <select name="product_id" onchange="this.form.submit()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 text-sm">
                    @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                        {{ $p->brand_name }} ({{ $p->dosage_strength ?? 'N/A' }}) - {{ $p->generic_name ?? 'General' }} [SKU: {{ $p->barcode_sku }}]
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <button type="submit" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Analyze Procurement
                </button>
            </div>
        </form>
    </div>

    @if($selectedProduct)
    <!-- Product Overview Card -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-3xl p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-mono text-emerald-400 font-bold uppercase tracking-wider">Drug Profile</span>
            <h2 class="text-2xl font-black font-display tracking-tight mt-0.5">{{ $selectedProduct->brand_name }}</h2>
            <p class="text-xs text-slate-300 italic">{{ $selectedProduct->generic_name }} &bull; {{ $selectedProduct->dosage_strength }}</p>
        </div>

        <div class="flex items-center gap-4 text-right">
            <div class="bg-white/10 px-4 py-2 rounded-xl backdrop-blur-xs">
                <span class="text-[10px] text-slate-400 uppercase">Current Unit Cost</span>
                <div class="text-base font-bold font-mono text-emerald-400">Rs. {{ number_format($selectedProduct->unit_cost_price, 2) }}</div>
            </div>
            <div class="bg-white/10 px-4 py-2 rounded-xl backdrop-blur-xs">
                <span class="text-[10px] text-slate-400 uppercase">Current Retail</span>
                <div class="text-base font-bold font-mono text-white">Rs. {{ number_format($selectedProduct->unit_retail_price, 2) }}</div>
            </div>
            <div class="bg-white/10 px-4 py-2 rounded-xl backdrop-blur-xs">
                <span class="text-[10px] text-slate-400 uppercase">Total GRN Conns</span>
                <div class="text-base font-bold font-mono text-teal-300">{{ $history->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Historical Suppliers & Consignments Log -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 font-bold text-xs uppercase tracking-wider text-slate-700 flex items-center justify-between">
            <span>Historical GRN Records for {{ $selectedProduct->brand_name }}</span>
            <span class="text-slate-500 font-mono font-normal">Found {{ $history->count() }} records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Receipt Date</th>
                        <th class="py-3 px-4">GRN Number</th>
                        <th class="py-3 px-4">Supplier / Distributor</th>
                        <th class="py-3 px-4 font-mono">Batch Number</th>
                        <th class="py-3 px-4 font-mono">Expiry Date</th>
                        <th class="py-3 px-4 text-center">Qty Received</th>
                        <th class="py-3 px-4 text-center">Bonus Qty</th>
                        <th class="py-3 px-4 text-right">Unit Cost Price</th>
                        <th class="py-3 px-4 text-right">Retail Price</th>
                        <th class="py-3 px-4 text-right">Total Invoiced</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($history as $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-mono">
                            {{ $item->grn->invoice_date ? $item->grn->invoice_date->format('d/m/Y') : '—' }}
                        </td>
                        <td class="py-3 px-4 font-mono font-bold">
                            <a href="{{ route('grn.show', $item->grn_id) }}" class="text-emerald-700 hover:underline">
                                {{ $item->grn->grn_number }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            {{ $item->grn->supplier->name ?? 'Direct Purchase' }}
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-slate-800">
                            {{ $item->batch_number }}
                        </td>
                        <td class="py-3 px-4 font-mono">
                            {{ $item->expiry_date->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold">
                            {{ $item->quantity_received }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-emerald-700">
                            +{{ $item->free_bonus_qty }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                            Rs. {{ number_format($item->cost_price, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-800">
                            Rs. {{ number_format($item->retail_price, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            Rs. {{ number_format($item->subtotal, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-10 text-center text-slate-400">
                            No GRN procurement history recorded for this drug yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
