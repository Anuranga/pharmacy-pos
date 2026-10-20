@extends('layouts.app')

@section('title', 'Customer vs Transaction Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-receipt text-emerald-600"></i>
                <span>1. Customer vs Transaction Report</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Detailed drill-down into sales invoices, item lines, and payment settlement modes per customer account.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.customer-vs-transaction') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs items-end">
            <div class="md:col-span-4">
                <label class="block font-semibold text-slate-700 mb-1">Select Customer</label>
                <select name="customer_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 font-semibold">
                    <option value="">-- All Customers & Walk-ins --</option>
                    @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ $selectedCustomerId == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
            </div>

            <div class="md:col-span-2">
                <label class="block font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
            </div>

            <div class="md:col-span-2">
                <label class="block font-semibold text-slate-700 mb-1">Payment Mode</label>
                <select name="payment_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-medium">
                    <option value="">-- All Modes --</option>
                    <option value="cash" {{ request('payment_type') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="card" {{ request('payment_type') === 'card' ? 'selected' : '' }}>Card</option>
                    <option value="qr" {{ request('payment_type') === 'qr' ? 'selected' : '' }}>LankaQR</option>
                    <option value="credit_lending" {{ request('payment_type') === 'credit_lending' ? 'selected' : '' }}>Credit / Lending</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Filter Transactions
                </button>
            </div>
        </form>
    </div>

    <!-- Summary KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Filtered Bills</span>
            <div class="text-xl font-bold font-mono text-slate-900 mt-1">{{ number_format($summary['count']) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Sales Volume</span>
            <div class="text-xl font-bold font-mono text-slate-900 mt-1">Rs. {{ number_format($summary['total_sales'], 2) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Collected</span>
            <div class="text-xl font-bold font-mono text-emerald-700 mt-1">Rs. {{ number_format($summary['total_paid'], 2) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Pending / Lending Due</span>
            <div class="text-xl font-bold font-mono text-rose-700 mt-1">Rs. {{ number_format($summary['total_due'], 2) }}</div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Invoice No</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Cashier</th>
                        <th class="py-3 px-4">Pay Mode</th>
                        <th class="py-3 px-4 text-right">Grand Total</th>
                        <th class="py-3 px-4 text-right">Amount Paid</th>
                        <th class="py-3 px-4 text-right">Balance Due</th>
                        <th class="py-3 px-4 text-center">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($sales as $s)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $s->invoice_number }}</td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            {{ $s->customer->name ?? 'Walk-in Customer' }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $s->cashier->name ?? 'Staff' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono {{ $s->payment_type === 'credit_lending' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $s->payment_type }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($s->grand_total, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-700">Rs. {{ number_format($s->amount_paid, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $s->balance_due > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rs. {{ number_format($s->balance_due, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('pos.receipt', $s->id) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800 font-bold">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center text-slate-400">No matching sales records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $sales->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
