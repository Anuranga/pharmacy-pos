@extends('layouts.app')

@section('title', 'GRN ' . $grn->grn_number)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('grn.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to GRN Register
        </a>

        <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Print Consignment Sheet
        </button>
    </div>

    <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm space-y-6">
        
        <!-- Sheet Header -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 pb-6 border-b border-slate-200">
            <div>
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-widest font-mono">Official Consignment Voucher</span>
                <h1 class="text-3xl font-extrabold font-display text-slate-900 tracking-tight mt-1">{{ $grn->grn_number }}</h1>
                <p class="text-xs text-slate-500 mt-1">Invoice Date: <span class="font-bold text-slate-700">{{ $grn->invoice_date->format('d F Y') }}</span></p>
            </div>

            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 uppercase">
                    Status: {{ $grn->status }}
                </span>
                <div class="text-2xl font-black font-display text-slate-900 font-mono mt-2">
                    Rs. {{ number_format($grn->total_amount, 2) }}
                </div>
            </div>
        </div>

        <!-- Supplier & Staff Details -->
        <div class="grid grid-cols-2 gap-6 text-xs p-4 bg-slate-50 rounded-2xl border border-slate-200">
            <div>
                <span class="text-[10px] font-bold uppercase text-slate-400">Supplier / Distributor</span>
                <h4 class="font-bold text-sm text-slate-900 mt-0.5">{{ $grn->supplier->name }}</h4>
                <p class="text-slate-500 mt-0.5">{{ $grn->supplier->address ?? 'No address registered' }}</p>
                <p class="text-slate-500">Contact: {{ $grn->supplier->phone }} | {{ $grn->supplier->contact_person }}</p>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase text-slate-400">Receiving Audit Info</span>
                <p class="text-slate-700 mt-0.5">Supplier Invoice: <strong class="font-mono text-slate-900">{{ $grn->invoice_number ?? 'N/A' }}</strong></p>
                <p class="text-slate-700">Received By: <strong class="text-slate-900">{{ $grn->receiver->name ?? 'Staff' }}</strong></p>
                <p class="text-slate-500 italic mt-1">Notes: {{ $grn->notes ?? 'None' }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-100 text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3">Item / Drug</th>
                        <th class="py-3 px-3 font-mono">Batch Number</th>
                        <th class="py-3 px-3 font-mono">Expiry Date</th>
                        <th class="py-3 px-3 text-center">Recv Qty</th>
                        <th class="py-3 px-3 text-center">Free Bonus</th>
                        <th class="py-3 px-3 text-right">Unit Cost</th>
                        <th class="py-3 px-3 text-right">Retail Price</th>
                        <th class="py-3 px-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($grn->items as $item)
                    <tr>
                        <td class="py-3 px-3">
                            <span class="font-bold text-slate-900">{{ $item->product->brand_name }}</span>
                            <span class="text-[11px] text-slate-500 block italic">{{ $item->product->generic_name }}</span>
                        </td>
                        <td class="py-3 px-3 font-mono font-bold text-slate-800">{{ $item->batch_number }}</td>
                        <td class="py-3 px-3 font-mono">{{ $item->expiry_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-3 text-center font-mono font-bold">{{ $item->quantity_received }}</td>
                        <td class="py-3 px-3 text-center font-mono text-emerald-700">+{{ $item->free_bonus_qty }}</td>
                        <td class="py-3 px-3 text-right font-mono">Rs. {{ number_format($item->cost_price, 2) }}</td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($item->retail_price, 2) }}</td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-6 border-t border-slate-200 flex justify-between items-center text-xs">
            <span class="text-slate-500">PharmaCare POS Procurement Subsystem</span>
            <span class="font-bold font-mono text-base text-slate-900">Grand Total: Rs. {{ number_format($grn->total_amount, 2) }}</span>
        </div>

    </div>

</div>
@endsection
