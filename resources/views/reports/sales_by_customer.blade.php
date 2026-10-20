@extends('layouts.app')

@section('title', 'Sales by Customer Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-trophy text-amber-500"></i>
                <span>5. Sales by Customer (Revenue Ranking)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Customer rankings by gross billing contribution, invoice frequency, and average order value.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.sales-by-customer') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs items-end">
            <div class="md:col-span-5">
                <label class="block font-semibold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>
            <div class="md:col-span-5">
                <label class="block font-semibold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Analyze Period
                </button>
            </div>
        </form>
    </div>

    <!-- Customer Rankings Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-ranking-star text-amber-500"></i>
            <span>Top Purchasing Customers</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4 text-center">Rank</th>
                        <th class="py-3 px-4">Customer Name</th>
                        <th class="py-3 px-4 font-mono">Phone</th>
                        <th class="py-3 px-4 text-center">Total Invoices</th>
                        <th class="py-3 px-4 text-right">Avg Order Value</th>
                        <th class="py-3 px-4 text-right">Total Revenue Contributed</th>
                        <th class="py-3 px-4 text-right">Current Debt Tab</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($customerSales as $idx => $cs)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-center">
                            @if($idx === 0)
                            <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-900 font-black flex items-center justify-center mx-auto text-xs shadow-xs">1</span>
                            @elseif($idx === 1)
                            <span class="w-6 h-6 rounded-full bg-slate-300 text-slate-900 font-bold flex items-center justify-center mx-auto text-xs">2</span>
                            @elseif($idx === 2)
                            <span class="w-6 h-6 rounded-full bg-amber-700 text-white font-bold flex items-center justify-center mx-auto text-xs">3</span>
                            @else
                            <span class="text-slate-400 font-bold font-mono">{{ $idx + 1 }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-900 text-sm">
                            <a href="{{ route('customers.show', $cs->id) }}" class="hover:text-emerald-600">
                                {{ $cs->name }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $cs->phone }}</td>
                        <td class="py-3 px-4 text-center font-mono font-bold">{{ $cs->total_invoices }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-800">Rs. {{ number_format($cs->avg_order_value, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-black text-emerald-700 text-sm">Rs. {{ number_format($cs->total_revenue, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $cs->current_debt_balance > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rs. {{ number_format($cs->current_debt_balance, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No registered customer purchases in this period.</td>
                    </tr>
                    @endforelse
                    
                    @if($walkinSales && $walkinSales->total_invoices > 0)
                    <tr class="bg-slate-50 font-bold">
                        <td class="py-3 px-4 text-center text-slate-400">—</td>
                        <td class="py-3 px-4 text-slate-700 italic">General Walk-in Customers (Unregistered)</td>
                        <td class="py-3 px-4 text-slate-400 font-mono">—</td>
                        <td class="py-3 px-4 text-center font-mono font-bold">{{ $walkinSales->total_invoices }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-500">—</td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-800 font-black">Rs. {{ number_format($walkinSales->total_revenue, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-400">Rs. 0.00</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
