@extends('layouts.app')

@section('title', 'GRN Procurement Register')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-list-check text-emerald-600"></i>
                <span>Goods Received Notes (GRN) Register</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Audit log of all drug consignments received from distributors and suppliers.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('grn.item-history') }}" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-semibold rounded-xl text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-clock-rotate-left text-teal-600"></i>
                <span>Item-Wise GRN History</span>
            </a>
            <a href="{{ route('grn.create') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>New GRN Entry</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('grn.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs">
            <div class="md:col-span-5 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search GRN number, Supplier Invoice number..."
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="md:col-span-4">
                <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 font-semibold">
                    <option value="">-- All Suppliers --</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Filter Records
                </button>
            </div>
        </form>
    </div>

    <!-- GRN Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">GRN Number</th>
                        <th class="py-3 px-4">Invoice Date</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">Supplier Invoice</th>
                        <th class="py-3 px-4 text-center">Items Count</th>
                        <th class="py-3 px-4 text-right">Total Amount (Rs.)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Received By</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($grns as $g)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">
                            <a href="{{ route('grn.show', $g->id) }}" class="text-emerald-700 hover:underline">
                                {{ $g->grn_number }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-mono">
                            {{ $g->invoice_date->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            {{ $g->supplier->name }}
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-500">
                            {{ $g->invoice_number ?? '—' }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold">
                                {{ $g->items->count() }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            Rs. {{ number_format($g->total_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                {{ $g->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ $g->receiver->name ?? 'Staff' }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('grn.show', $g->id) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                                View &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-truck-ramp-box text-4xl mb-2 text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-600">No GRN records found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $grns->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
