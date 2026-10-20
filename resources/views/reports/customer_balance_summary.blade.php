@extends('layouts.app')

@section('title', 'Customer Balance Summary')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-scale-balanced text-blue-600"></i>
                <span>4. Customer Balance Summary</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Consolidated debt balances of all registered customer accounts compared to approved credit caps.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Summary KPI Header -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Approved Credit Facility</span>
            <div class="text-2xl font-black font-mono text-slate-900 mt-1">Rs. {{ number_format($totalLimit, 2) }}</div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Open Debt Balance</span>
            <div class="text-2xl font-black font-mono text-rose-600 mt-1">Rs. {{ number_format($totalDebt, 2) }}</div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Credit Utilization Ratio</span>
            <div class="text-2xl font-black font-mono text-emerald-600 mt-1">
                {{ $totalLimit > 0 ? number_format(($totalDebt / $totalLimit) * 100, 1) : 0 }}%
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-sm text-slate-900">Consolidated Customer Ledger Summary</h3>
            <a href="{{ route('reports.customer-balance-summary', ['filter' => request('filter') === 'debt_only' ? '' : 'debt_only']) }}"
               class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-semibold {{ request('filter') === 'debt_only' ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700' }}">
                {{ request('filter') === 'debt_only' ? 'Showing Debt Only' : 'Show Debt Only' }}
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Customer Name</th>
                        <th class="py-3 px-4 font-mono">Phone Number</th>
                        <th class="py-3 px-4 font-mono">NIC Number</th>
                        <th class="py-3 px-4 text-right">Credit Limit</th>
                        <th class="py-3 px-4 text-right">Current Debt Balance</th>
                        <th class="py-3 px-4 text-right">Available Headroom</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $c->name }}</td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $c->phone }}</td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $c->nic_number ?? '—' }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-800">Rs. {{ number_format($c->credit_limit, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $c->current_debt_balance > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rs. {{ number_format($c->current_debt_balance, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                            Rs. {{ number_format($c->remaining_credit, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('customers.show', $c->id) }}" class="text-emerald-600 hover:underline font-bold">
                                View Profile &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No matching customers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
