@extends('layouts.app')

@section('title', 'Supplier Return Debit Note #' . $purchaseReturn->return_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    <div class="no-print flex items-center justify-between">
        <a href="{{ route('returns.index', ['tab' => 'supplier']) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Returns Hub
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('returns.supplier.create') }}" class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700">
                + Another Shop Return
            </a>
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print Debit Note
            </button>
        </div>
    </div>

    <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                    Shop GRN Return / Supplier Debit Note
                </span>
                <h1 class="text-2xl font-extrabold font-display text-slate-900 mt-1 font-mono">{{ $purchaseReturn->return_number }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Processed on {{ $purchaseReturn->created_at->format('d M Y, h:i A') }} by <strong>{{ $purchaseReturn->processor->name ?? 'Admin' }}</strong>
                </p>
            </div>

            <div class="text-left sm:text-right text-xs space-y-1">
                <div>
                    <span class="text-slate-400">Supplier:</span>
                    <strong class="text-slate-900 ml-1">{{ $purchaseReturn->supplier->name ?? 'Supplier' }}</strong>
                </div>
                <div>
                    <span class="text-slate-400">GRN Reference:</span>
                    <strong class="font-mono text-indigo-700 ml-1">{{ $purchaseReturn->grn->grn_number ?? 'Direct Batch Return' }}</strong>
                </div>
                <div>
                    <span class="text-slate-400">Reason:</span>
                    <span class="font-semibold text-amber-700 ml-1">{{ $purchaseReturn->reason }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Medication</th>
                        <th class="py-3 px-4">Batch No</th>
                        <th class="py-3 px-4 text-center">Returned UOM</th>
                        <th class="py-3 px-4 text-center">Base Units Deducted</th>
                        <th class="py-3 px-4 text-right">Unit Cost</th>
                        <th class="py-3 px-4 text-right">Return Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @foreach($purchaseReturn->items as $idx => $item)
                    <tr>
                        <td class="py-3 px-4 font-mono text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $item->product->brand_name ?? 'Item' }}</td>
                        <td class="py-3 px-4 font-mono font-bold">{{ $item->batch_number }}</td>
                        <td class="py-3 px-4 text-center font-mono">
                            @if($item->return_uom === 'pack' && $item->packs_returned > 0)
                                <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold">
                                    {{ $item->packs_returned }} Pack(s) x {{ $item->units_per_pack }}
                                </span>
                            @else
                                <span>{{ $item->quantity_returned }} {{ $item->product->base_uom ?? 'Units' }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-rose-600">
                            -{{ $item->quantity_returned }} {{ $item->product->base_uom ?? 'Units' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono">Rs. {{ number_format($item->unit_cost, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="text-xs text-slate-500">
                @if($purchaseReturn->notes)
                    <strong>Notes:</strong> {{ $purchaseReturn->notes }}
                @endif
            </div>
            <div class="bg-slate-900 text-white rounded-2xl px-6 py-4 flex items-center gap-6">
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Settlement</div>
                    <div class="text-xs font-bold text-indigo-400 uppercase">{{ str_replace('_', ' ', $purchaseReturn->settlement_method) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Total Debit Note Value</div>
                    <div class="text-xl font-black font-mono text-white">Rs. {{ number_format($purchaseReturn->total_return_amount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
