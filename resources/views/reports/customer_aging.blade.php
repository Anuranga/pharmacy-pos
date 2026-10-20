@extends('layouts.app')

@section('title', 'Customer A/R Aging Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-business-time text-rose-600"></i>
                <span>3. Customer A/R Aging Report</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Accounts Receivable debt breakdown stratified into 0-30, 31-60, 61-90, and 90+ days overdue buckets.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Overall Aging Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Receivables</span>
            <div class="text-xl font-black font-mono text-slate-900 mt-1">Rs. {{ number_format($agingData['total_receivable'], 2) }}</div>
        </div>

        <div class="bg-emerald-50 p-4 rounded-3xl border border-emerald-200 shadow-xs">
            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Current (0-30 Days)</span>
            <div class="text-xl font-black font-mono text-emerald-800 mt-1">Rs. {{ number_format($agingData['current'], 2) }}</div>
        </div>

        <div class="bg-yellow-50 p-4 rounded-3xl border border-yellow-200 shadow-xs">
            <span class="text-[10px] font-bold text-yellow-700 uppercase tracking-wider">31-60 Days Overdue</span>
            <div class="text-xl font-black font-mono text-yellow-800 mt-1">Rs. {{ number_format($agingData['tier_30_60'], 2) }}</div>
        </div>

        <div class="bg-orange-50 p-4 rounded-3xl border border-orange-200 shadow-xs">
            <span class="text-[10px] font-bold text-orange-700 uppercase tracking-wider">61-90 Days Overdue</span>
            <div class="text-xl font-black font-mono text-orange-800 mt-1">Rs. {{ number_format($agingData['tier_60_90'], 2) }}</div>
        </div>

        <div class="bg-rose-50 p-4 rounded-3xl border border-rose-200 shadow-xs">
            <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">90+ Days Critical</span>
            <div class="text-xl font-black font-mono text-rose-800 mt-1">Rs. {{ number_format($agingData['tier_90_plus'], 2) }}</div>
        </div>
    </div>

    <!-- Customer Breakdown Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6">
        <h3 class="font-display font-bold text-base text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-users text-emerald-600"></i> Debtor Aging Ledger Roster
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Customer Name & Phone</th>
                        <th class="py-3 px-4 text-right">0-30 Days</th>
                        <th class="py-3 px-4 text-right">31-60 Days</th>
                        <th class="py-3 px-4 text-right">61-90 Days</th>
                        <th class="py-3 px-4 text-right">90+ Days</th>
                        <th class="py-3 px-4 text-right">Total Outstanding</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($agingData['customers'] as $item)
                    @php $cust = $item['customer']; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            {{ $cust ? $cust->name : 'Walk-in Tab' }}
                            <span class="text-[10px] text-slate-400 font-mono block font-normal">{{ $cust ? $cust->phone : '' }}</span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $item['current'] > 0 ? 'text-emerald-700 font-bold' : 'text-slate-400' }}">
                            Rs. {{ number_format($item['current'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $item['tier_30_60'] > 0 ? 'text-yellow-700 font-bold' : 'text-slate-400' }}">
                            Rs. {{ number_format($item['tier_30_60'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $item['tier_60_90'] > 0 ? 'text-orange-700 font-bold' : 'text-slate-400' }}">
                            Rs. {{ number_format($item['tier_60_90'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $item['tier_90_plus'] > 0 ? 'text-rose-700 font-bold' : 'text-slate-400' }}">
                            Rs. {{ number_format($item['tier_90_plus'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-black text-slate-900 text-sm">
                            Rs. {{ number_format($item['total'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            @if($cust)
                            <a href="{{ route('customers.show', $cust->id) }}" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold">
                                Settle &rarr;
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-400">No overdue receivables recorded. All debtor accounts are settled!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
