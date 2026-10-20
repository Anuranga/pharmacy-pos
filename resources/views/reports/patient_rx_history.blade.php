@extends('layouts.app')

@section('title', 'Patient Rx & Controlled Substances Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-file-prescription text-cyan-600"></i>
                <span>7. Patient Rx & Controlled Drug Regulatory Log</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Regulatory tracking of prescription-only medications and Form-D controlled dangerous drugs indexed by doctor SLMC and patient credentials.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.patient-rx-history') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs items-end">
            <div class="md:col-span-3">
                <label class="block font-semibold text-slate-700 mb-1">Prescribing Doctor</label>
                <input type="text" name="doctor_name" value="{{ request('doctor_name') }}" placeholder="Search Doctor name..."
                       class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>

            <div class="md:col-span-3">
                <label class="block font-semibold text-slate-700 mb-1">Patient Name</label>
                <input type="text" name="patient_name" value="{{ request('patient_name') }}" placeholder="Search Patient name..."
                       class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>

            <div class="md:col-span-2">
                <label class="block font-semibold text-slate-700 mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>

            <div class="md:col-span-2">
                <label class="block font-semibold text-slate-700 mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Filter Log
                </button>
            </div>
        </form>
    </div>

    <!-- Prescription Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3 px-4">Patient Name</th>
                        <th class="py-3 px-4">Prescribing Doctor & SLMC</th>
                        <th class="py-3 px-4">Dispensed Medications</th>
                        <th class="py-3 px-4 text-right">Invoice Total</th>
                        <th class="py-3 px-4 text-center">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($prescriptions as $rx)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $rx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $rx->sale->invoice_number ?? '—' }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $rx->patient_name }}</td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-800 block">{{ $rx->doctor_name }}</span>
                            <span class="text-[10px] text-slate-500 font-mono">Reg: {{ $rx->doctor_reg_no ?? 'N/A' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            @if($rx->sale && $rx->sale->items)
                            <div class="space-y-1">
                                @foreach($rx->sale->items as $it)
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-slate-800">{{ $it->product->brand_name }}</span>
                                    <span class="text-slate-500 font-mono">x{{ $it->quantity }}</span>
                                    @if($it->product->is_controlled)
                                    <span class="text-[9px] bg-purple-100 text-purple-700 font-bold px-1 rounded uppercase">Controlled</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            Rs. {{ number_format($rx->sale->grand_total ?? 0, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($rx->sale)
                            <a href="{{ route('pos.receipt', $rx->sale_id) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800 font-bold">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No prescription records found matching criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-4">
            {{ $prescriptions->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
