@extends('layouts.app')

@section('title', 'Supplier vs Transaction Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-truck-moving text-sky-600"></i>
                <span>2. Supplier vs Transaction Report</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Cross-sectional analysis of GRN consignments, invoices, and disbursement payouts per supplier.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.supplier-vs-transaction') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs items-end">
            <div class="md:col-span-5">
                <label class="block font-semibold text-slate-700 mb-1">Select Supplier / Distributor</label>
                <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 font-semibold">
                    <option value="">-- All Suppliers --</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ $selectedSupplierId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="block font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
            </div>

            <div class="md:col-span-3">
                <label class="block font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
            </div>

            <div class="md:col-span-1">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    @if($selectedSupplier)
    <div class="bg-slate-900 text-white rounded-3xl p-5 flex items-center justify-between">
        <div>
            <span class="text-xs font-mono text-sky-400 uppercase tracking-widest font-bold">Selected Supplier Profile</span>
            <h3 class="text-xl font-bold font-display mt-0.5">{{ $selectedSupplier->name }}</h3>
            <p class="text-xs text-slate-400">Credit Terms: {{ $selectedSupplier->credit_period_days }} Days &bull; Contact: {{ $selectedSupplier->phone }}</p>
        </div>
        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-slate-400">Current Outstanding Liability</span>
            <div class="text-2xl font-black font-mono text-rose-400">Rs. {{ number_format($selectedSupplier->current_balance, 2) }}</div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- GRNs Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-5 space-y-3">
            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-emerald-600"></i>
                <span>Received Consignments (GRNs)</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">GRN #</th>
                            <th class="py-2.5 px-3">Supplier</th>
                            <th class="py-2.5 px-3 text-right">Invoiced</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($grns as $g)
                        <tr>
                            <td class="py-2.5 px-3 font-mono text-slate-500">{{ $g->invoice_date->format('d/m/Y') }}</td>
                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900">{{ $g->grn_number }}</td>
                            <td class="py-2.5 px-3 text-slate-700">{{ $g->supplier->name }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($g->total_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">No GRNs recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-2">{{ $grns->withQueryString()->links() }}</div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-5 space-y-3">
            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-money-check-dollar text-sky-600"></i>
                <span>Disbursements & Payouts</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Method</th>
                            <th class="py-2.5 px-3">Reference</th>
                            <th class="py-2.5 px-3 text-right">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payments as $p)
                        <tr>
                            <td class="py-2.5 px-3 font-mono text-slate-500">{{ $p->payment_date->format('d/m/Y') }}</td>
                            <td class="py-2.5 px-3 font-semibold">{{ $p->payment_method }}</td>
                            <td class="py-2.5 px-3 font-mono text-slate-600">{{ $p->reference_number ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">Rs. {{ number_format($p->amount_paid, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">No disbursements recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-2">{{ $payments->withQueryString()->links() }}</div>
        </div>

    </div>

</div>
@endsection
