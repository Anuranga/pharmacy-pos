@extends('layouts.app')

@section('title', 'Real-Time Inventory Valuation Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-boxes-packing text-indigo-600"></i>
                <span>8. Inventory Asset Valuation Report</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Real-time stock valuation audit comparing purchase cost asset value against projected retail sales revenue.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Valuation Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Units on Hand</span>
            <div class="text-2xl font-black font-mono text-slate-900 mt-1">{{ number_format($totalStockUnits) }}</div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Asset Value (Cost Basis)</span>
            <div class="text-2xl font-black font-mono text-indigo-600 mt-1">Rs. {{ number_format($totalCostValuation, 2) }}</div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Projected Retail Revenue</span>
            <div class="text-2xl font-black font-mono text-emerald-600 mt-1">Rs. {{ number_format($totalRetailValuation, 2) }}</div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Projected Gross Margin</span>
            <div class="text-2xl font-black font-mono text-teal-600 mt-1">
                Rs. {{ number_format($projectedMargin, 2) }}
                <span class="text-xs font-bold text-slate-500 block">({{ number_format($projectedMarginPercent, 1) }}% Margin)</span>
            </div>
        </div>
    </div>

    <!-- Batches Valuation Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-base text-slate-900">Active Inventory Batches Valuation Roster</h3>
            <span class="text-xs font-mono text-slate-400">Total Batches: {{ $batches->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Drug / Brand Name</th>
                        <th class="py-3 px-4 font-mono">Batch Number</th>
                        <th class="py-3 px-4 font-mono">Expiry Date</th>
                        <th class="py-3 px-4 text-center">Stock Qty</th>
                        <th class="py-3 px-4 text-right">Unit Cost</th>
                        <th class="py-3 px-4 text-right">Unit Retail</th>
                        <th class="py-3 px-4 text-right">Total Cost Value</th>
                        <th class="py-3 px-4 text-right">Projected Retail</th>
                        <th class="py-3 px-4 text-right">Unrealized Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($batches as $b)
                    @php
                        $costVal = $b->stock_quantity * (float) $b->purchase_cost;
                        $retVal = $b->stock_quantity * (float) $b->selling_price;
                        $margin = $retVal - $costVal;
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900 block text-sm">{{ $b->product->brand_name }}</span>
                            <span class="text-[10px] text-slate-500 italic">{{ $b->product->generic_name }}</span>
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-slate-800">{{ $b->batch_number }}</td>
                        <td class="py-3 px-4 font-mono">{{ $b->expiry_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-slate-900">{{ $b->stock_quantity }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">Rs. {{ number_format($b->purchase_cost, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($b->selling_price, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-indigo-700">Rs. {{ number_format($costVal, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">Rs. {{ number_format($retVal, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-teal-700">Rs. {{ number_format($margin, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">No active stock in inventory.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
