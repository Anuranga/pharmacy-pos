@extends('layouts.app')

@section('title', 'Returns Hub - Customer & GRN Returns')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ activeTab: '{{ $tab === 'supplier' ? 'supplier' : 'customer' }}' }">

    <!-- Top Header & Action Buttons -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                    Inventory & Financial Returns
                </span>
            </div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-rotate-left text-emerald-600"></i>
                <span>Customer & Shop (GRN) Returns Hub</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Process customer billed item returns (restock or write-off) and shop GRN consignment returns to suppliers.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('returns.customer.create') }}"
               class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-2 transition-all">
                <i class="fa-solid fa-receipt"></i>
                <span>+ New Customer Bill Return</span>
            </a>

            @if(auth()->user()->isAdmin())
            <a href="{{ route('returns.supplier.create') }}"
               class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm flex items-center gap-2 transition-all">
                <i class="fa-solid fa-truck-Arrow-right fa-flip-horizontal"></i>
                <span>+ New Shop (GRN) Return</span>
            </a>
            @endif
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Today Customer Returns</div>
            <div class="text-xl font-black font-display text-rose-600 mt-1 font-mono">
                Rs. {{ number_format($stats['today_customer_returns'], 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">{{ $stats['today_customer_returns_count'] }} return notes today</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Customer Refunds</div>
            <div class="text-xl font-black font-display text-slate-800 mt-1 font-mono">
                Rs. {{ number_format($stats['total_customer_returns'], 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">Cumulative POS billed returns</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Shop GRN Returns</div>
            <div class="text-xl font-black font-display text-indigo-700 mt-1 font-mono">
                Rs. {{ number_format($stats['total_supplier_returns'], 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">{{ $stats['total_supplier_returns_count'] }} supplier debit notes</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Stock & Ledger Sync</div>
            <div class="text-xs font-semibold text-emerald-700 mt-1 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>Auto Batch & Payable Adjusted</span>
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">FEFO batches & A/R, A/P ledgers synced</div>
        </div>
    </div>

    <!-- Tabs Switcher -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 pt-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50/60">
            <div class="flex items-center space-x-2">
                <button type="button"
                        @click="activeTab = 'customer'"
                        :class="activeTab === 'customer' ? 'border-emerald-600 text-emerald-700 bg-white shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="px-4 py-2.5 rounded-t-xl border-b-2 text-xs font-bold flex items-center gap-2 transition-all">
                    <i class="fa-solid fa-user-tag"></i>
                    <span>Customer Billed Returns (Sales Returns)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-mono">{{ $saleReturns->total() }}</span>
                </button>

                <button type="button"
                        @click="activeTab = 'supplier'"
                        :class="activeTab === 'supplier' ? 'border-indigo-600 text-indigo-700 bg-white shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="px-4 py-2.5 rounded-t-xl border-b-2 text-xs font-bold flex items-center gap-2 transition-all">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                    <span>Shop GRN Returns (Supplier Debit Notes)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-100 text-indigo-800 font-mono">{{ $purchaseReturns->total() }}</span>
                </button>
            </div>
        </div>

        <!-- TAB 1: Customer Sales Returns Table -->
        <div x-show="activeTab === 'customer'" class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Return No</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Original Invoice</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Returned Items</th>
                        <th class="py-3 px-4">Reason</th>
                        <th class="py-3 px-4">Refund Mode</th>
                        <th class="py-3 px-4 text-right">Refund Amount</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($saleReturns as $sr)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-emerald-700">
                            <a href="{{ route('returns.customer.show', $sr->id) }}" class="hover:underline">{{ $sr->return_number }}</a>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-500">
                            {{ $sr->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td class="py-3 px-4 font-mono">
                            @if($sr->sale)
                            <a href="{{ route('pos.receipt', $sr->sale_id) }}" target="_blank" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold">
                                {{ $sr->sale->invoice_number }}
                            </a>
                            @else
                            <span class="text-slate-400">N/A</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            {{ $sr->customer->name ?? 'Walk-in Customer' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-800">{{ $sr->items->sum('quantity_returned') }} units</span>
                            <span class="text-[10px] text-slate-400 block">
                                ({{ $sr->items->count() }} line items)
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-semibold">
                                {{ $sr->reason }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            @if($sr->refund_method === 'cash')
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase">Cash Refund</span>
                            @elseif($sr->refund_method === 'credit_reduction')
                                <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold uppercase">Debt Reduced</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200 text-[10px] font-bold uppercase">Exchange</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-600">
                            Rs. {{ number_format($sr->total_refund_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('returns.customer.show', $sr->id) }}"
                               class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 text-xs font-semibold transition-colors">
                                View / Print
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            No customer sales returns recorded yet. Click <strong>+ New Customer Bill Return</strong> to process a return.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-slate-100">
                {{ $saleReturns->appends(['tab' => 'customer'])->links() }}
            </div>
        </div>

        <!-- TAB 2: Shop / Supplier GRN Returns Table -->
        <div x-show="activeTab === 'supplier'" x-cloak class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Debit Note No</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">GRN Ref</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">Returned Stock</th>
                        <th class="py-3 px-4">Reason</th>
                        <th class="py-3 px-4">Settlement Mode</th>
                        <th class="py-3 px-4 text-right">Return Value</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($purchaseReturns as $pr)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-indigo-700">
                            <a href="{{ route('returns.supplier.show', $pr->id) }}" class="hover:underline">{{ $pr->return_number }}</a>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-500">
                            {{ $pr->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td class="py-3 px-4 font-mono">
                            @if($pr->grn)
                            <a href="{{ route('grn.show', $pr->grn_id) }}" class="px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold">
                                {{ $pr->grn->grn_number }}
                            </a>
                            @else
                            <span class="text-slate-400">Direct Batch</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-800">
                            {{ $pr->supplier->name ?? 'Unknown Supplier' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-800">{{ $pr->items->sum('quantity_returned') }} base units</span>
                            <span class="text-[10px] text-slate-400 block">({{ $pr->items->count() }} batch lines)</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-semibold">
                                {{ $pr->reason }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            @if($pr->settlement_method === 'payable_deduction')
                                <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold uppercase">Deducted from Payable</span>
                            @elseif($pr->settlement_method === 'cash_refund')
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase">Supplier Cash Refund</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200 text-[10px] font-bold uppercase">Replacement</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-indigo-700">
                            Rs. {{ number_format($pr->total_return_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('returns.supplier.show', $pr->id) }}"
                               class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-indigo-600 hover:text-white text-slate-700 text-xs font-semibold transition-colors">
                                View / Print
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            No shop GRN returns recorded yet. Click <strong>+ New Shop (GRN) Return</strong> to return stock to a supplier.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-slate-100">
                {{ $purchaseReturns->appends(['tab' => 'supplier'])->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
