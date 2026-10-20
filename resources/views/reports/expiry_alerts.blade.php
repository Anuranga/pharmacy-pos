@extends('layouts.app')

@section('title', 'FEFO Expiry Alerts Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                <span>9. FEFO Expiry Alerts Dashboard</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Multi-tier shelf life surveillance: Expired (Red - Auto Blocked from POS), Critical &le; 30 days (Orange), and Near Expiry &le; 90 days (Yellow).</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Alert Filters (Pills) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        
        <!-- Expired (Red) -->
        <a href="{{ route('reports.expiry-alerts', ['filter' => 'expired']) }}" 
           class="p-5 rounded-3xl border transition-all flex items-center justify-between {{ $filter === 'expired' ? 'bg-red-500 text-white shadow-lg shadow-red-500/20 border-red-600' : 'bg-red-50 border-red-200 text-red-900 hover:bg-red-100/80' }}">
            <div>
                <span class="text-xs uppercase font-bold tracking-wider {{ $filter === 'expired' ? 'text-red-100' : 'text-red-700' }}">Expired Batches (Blocked)</span>
                <div class="text-3xl font-black font-mono mt-1">{{ $counts['expired'] }}</div>
                <span class="text-[11px] {{ $filter === 'expired' ? 'text-red-100' : 'text-red-600' }}">Immediate Write-Off / Return</span>
            </div>
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl {{ $filter === 'expired' ? 'bg-white/20 text-white' : 'bg-red-200 text-red-700' }}">
                <i class="fa-solid fa-ban"></i>
            </div>
        </a>

        <!-- Critical <= 30 Days (Orange) -->
        <a href="{{ route('reports.expiry-alerts', ['filter' => 'critical']) }}" 
           class="p-5 rounded-3xl border transition-all flex items-center justify-between {{ $filter === 'critical' ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20 border-orange-600' : 'bg-orange-50 border-orange-200 text-orange-900 hover:bg-orange-100/80' }}">
            <div>
                <span class="text-xs uppercase font-bold tracking-wider {{ $filter === 'critical' ? 'text-orange-100' : 'text-orange-700' }}">Critical Expiry (&le; 30 Days)</span>
                <div class="text-3xl font-black font-mono mt-1">{{ $counts['critical'] }}</div>
                <span class="text-[11px] {{ $filter === 'critical' ? 'text-orange-100' : 'text-orange-600' }}">Expiring This Month</span>
            </div>
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl {{ $filter === 'critical' ? 'bg-white/20 text-white' : 'bg-orange-200 text-orange-700' }}">
                <i class="fa-solid fa-bell"></i>
            </div>
        </a>

        <!-- Near Expiry <= 90 Days (Yellow) -->
        <a href="{{ route('reports.expiry-alerts', ['filter' => 'near']) }}" 
           class="p-5 rounded-3xl border transition-all flex items-center justify-between {{ $filter === 'near' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20 border-amber-600' : 'bg-yellow-50 border-yellow-200 text-yellow-900 hover:bg-yellow-100/80' }}">
            <div>
                <span class="text-xs uppercase font-bold tracking-wider {{ $filter === 'near' ? 'text-amber-100' : 'text-yellow-800' }}">Near Expiry (&le; 90 Days)</span>
                <div class="text-3xl font-black font-mono mt-1">{{ $counts['near'] }}</div>
                <span class="text-[11px] {{ $filter === 'near' ? 'text-amber-100' : 'text-yellow-700' }}">Expiring in 1-3 Months</span>
            </div>
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl {{ $filter === 'near' ? 'bg-white/20 text-white' : 'bg-yellow-200 text-yellow-800' }}">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
        </a>

    </div>

    <!-- Filter Toggle Bar -->
    <div class="flex items-center justify-between text-xs">
        <span class="font-bold text-slate-700">Displaying Alert Batches:</span>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.expiry-alerts', ['filter' => 'all_alerts']) }}" 
               class="px-3 py-1.5 rounded-xl border text-xs font-semibold {{ $filter === 'all_alerts' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300' }}">
                All Alerts (&le; 90 Days)
            </a>
            <a href="{{ route('reports.expiry-alerts', ['filter' => 'expired']) }}" 
               class="px-3 py-1.5 rounded-xl border text-xs font-semibold {{ $filter === 'expired' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-red-700 border-slate-300' }}">
                Expired Only
            </a>
            <a href="{{ route('reports.expiry-alerts', ['filter' => 'critical']) }}" 
               class="px-3 py-1.5 rounded-xl border text-xs font-semibold {{ $filter === 'critical' ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-orange-700 border-slate-300' }}">
                &le; 30 Days
            </a>
            <a href="{{ route('reports.expiry-alerts', ['filter' => 'near']) }}" 
               class="px-3 py-1.5 rounded-xl border text-xs font-semibold {{ $filter === 'near' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-yellow-700 border-slate-300' }}">
                &le; 90 Days
            </a>
        </div>
    </div>

    <!-- Alert Batches Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Medication & Form</th>
                        <th class="py-3 px-4 font-mono">Batch Number</th>
                        <th class="py-3 px-4 font-mono">Expiry Date</th>
                        <th class="py-3 px-4">Shelf Status</th>
                        <th class="py-3 px-4 text-center">Remaining Stock</th>
                        <th class="py-3 px-4 text-right">Cost Value at Risk</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($batches as $b)
                    @php 
                        $status = $b->expiry_status; 
                        $valueAtRisk = $b->stock_quantity * (float) $b->purchase_cost;
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            {{ $b->product->brand_name }}
                            <span class="text-[10px] text-slate-400 font-normal italic block">{{ $b->product->generic_name }}</span>
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-slate-800">{{ $b->batch_number }}</td>
                        <td class="py-3 px-4 font-mono">{{ $b->expiry_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $status['class'] }}">
                                {{ $status['status'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-black text-sm text-slate-900">
                            {{ $b->stock_quantity }} {{ $b->product->base_uom }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-700">
                            Rs. {{ number_format($valueAtRisk, 2) }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $b->supplier->name ?? 'Direct Purchase' }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('master.products.show', $b->product_id) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs">
                                Adjust Stock &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-400">
                            <i class="fa-solid fa-circle-check text-4xl text-emerald-500 mb-2"></i>
                            <p class="text-sm font-semibold text-slate-700">No batches detected in this expiry risk window!</p>
                            <p class="text-xs text-slate-400">All medications have healthy shelf lives.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-4">
            {{ $batches->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
