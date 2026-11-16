@extends('layouts.app')

@section('title', 'Sales Return #' . $saleReturn->return_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    <div class="no-print flex items-center justify-between">
        <a href="{{ route('returns.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Returns Hub
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('returns.customer.create') }}" class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700">
                + Another Customer Return
            </a>
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print Return Note
            </button>
        </div>
    </div>

    <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200">
                    Customer Sales Return / Credit Note
                </span>
                <h1 class="text-2xl font-extrabold font-display text-slate-900 mt-1 font-mono">{{ $saleReturn->return_number }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Processed on {{ $saleReturn->created_at->format('d M Y, h:i A') }} by <strong>{{ $saleReturn->processor->name ?? 'Staff' }}</strong>
                </p>
            </div>

            <div class="text-left sm:text-right text-xs space-y-1">
                <div>
                    <span class="text-slate-400">Original Invoice:</span>
                    <strong class="font-mono text-slate-900 ml-1">{{ $saleReturn->sale->invoice_number ?? 'N/A' }}</strong>
                </div>
                <div>
                    <span class="text-slate-400">Customer:</span>
                    <strong class="text-slate-800 ml-1">{{ $saleReturn->customer->name ?? 'Walk-in Customer' }}</strong>
                </div>
                <div>
                    <span class="text-slate-400">Reason:</span>
                    <span class="font-semibold text-amber-700 ml-1">{{ $saleReturn->reason }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Medication</th>
                        <th class="py-3 px-4">Batch</th>
                        <th class="py-3 px-4 text-center">Returned Qty</th>
                        <th class="py-3 px-4">Inventory Status</th>
                        <th class="py-3 px-4 text-right">Unit Price</th>
                        <th class="py-3 px-4 text-right">Refund Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @foreach($saleReturn->items as $idx => $item)
                    <tr>
                        <td class="py-3 px-4 font-mono text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $item->product->brand_name ?? 'Item' }}</td>
                        <td class="py-3 px-4 font-mono">{{ $item->batch->batch_number ?? 'N/A' }}</td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-slate-900">
                            {{ $item->quantity_returned }} {{ $item->product->base_uom ?? 'Units' }}
                        </td>
                        <td class="py-3 px-4">
                            @if($item->restock_status === 'restocked')
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">Restocked to Shelf</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold">Discarded / Damaged</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-mono">Rs. {{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="text-xs text-slate-500">
                @if($saleReturn->notes)
                    <strong>Notes:</strong> {{ $saleReturn->notes }}
                @endif
            </div>
            <div class="bg-slate-900 text-white rounded-2xl px-6 py-4 flex items-center gap-6">
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Refund Mode</div>
                    <div class="text-xs font-bold text-emerald-400 uppercase">{{ str_replace('_', ' ', $saleReturn->refund_method) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Total Refunded</div>
                    <div class="text-xl font-black font-mono text-white">Rs. {{ number_format($saleReturn->total_refund_amount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
