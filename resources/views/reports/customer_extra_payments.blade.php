@extends('layouts.app')

@section('title', 'Customer Extra Payments & Advances')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-wallet text-emerald-600"></i>
                <span>6. Customer Extra Payments & Advance Deposits</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Audit log of customer advance funds, deposit receipts, and unallocated credits.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Summary Box -->
    <div class="bg-emerald-900 text-white rounded-3xl p-6 shadow-xl flex items-center justify-between">
        <div>
            <span class="text-xs uppercase tracking-widest text-emerald-300 font-bold">Total Advance Deposits Logged</span>
            <div class="text-3xl font-black font-display font-mono text-emerald-300 mt-1">
                Rs. {{ number_format($totalExtra, 2) }}
            </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-white/10 text-white flex items-center justify-center text-xl backdrop-blur-xs">
            <i class="fa-solid fa-piggy-bank"></i>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.customer-extra-payments') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs items-end">
            <div class="md:col-span-5">
                <label class="block font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>
            <div class="md:col-span-5">
                <label class="block font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Payment Date</th>
                        <th class="py-3 px-4">Customer Name</th>
                        <th class="py-3 px-4 font-mono">Customer Phone</th>
                        <th class="py-3 px-4">Method</th>
                        <th class="py-3 px-4 text-right">Advance Amount</th>
                        <th class="py-3 px-4">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($extraPayments as $ep)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $ep->payment_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900">
                            <a href="{{ route('customers.show', $ep->customer_id) }}" class="hover:text-emerald-600">
                                {{ $ep->customer->name ?? 'Unknown' }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $ep->customer->phone ?? '—' }}</td>
                        <td class="py-3 px-4 font-semibold">{{ $ep->payment_method }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 text-sm">
                            Rs. {{ number_format($ep->amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-slate-500 italic">{{ $ep->notes ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No advance deposits found in this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-4">
            {{ $extraPayments->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
