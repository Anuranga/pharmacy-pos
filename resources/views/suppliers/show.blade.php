@extends('layouts.app')

@section('title', $supplier->name . ' - Supplier Ledger')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ payModal: false, selectedGrnId: '' }">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('suppliers.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Suppliers
        </a>

        <button type="button" @click="payModal = true; selectedGrnId = '';"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-money-check-dollar"></i>
            <span>Register Payout / Disbursement</span>
        </button>
    </div>

    <!-- Supplier Profile Header -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
            <div>
                <span class="text-xs font-mono font-bold text-emerald-600 uppercase tracking-widest">Supplier Accounts Payable Ledger</span>
                <h1 class="text-3xl font-extrabold font-display text-slate-900 tracking-tight mt-1">{{ $supplier->name }}</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Contact: <strong class="text-slate-800">{{ $supplier->contact_person ?? 'N/A' }}</strong> | 
                    Phone: <strong class="font-mono text-slate-800">{{ $supplier->phone ?? 'N/A' }}</strong> | 
                    Email: <strong class="text-slate-800">{{ $supplier->email ?? 'N/A' }}</strong>
                </p>
                <p class="text-xs text-slate-500 mt-0.5">Address: {{ $supplier->address ?? 'No address registered' }}</p>
            </div>

            <!-- Financial Counters -->
            <div class="flex items-center gap-3 text-right">
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Credit Period</span>
                    <div class="text-sm font-bold font-mono text-slate-800">{{ $supplier->credit_period_days }} Days</div>
                </div>

                <div class="bg-rose-50 p-4 rounded-2xl border border-rose-200">
                    <span class="text-[10px] font-bold text-rose-600 uppercase">Outstanding Liability (AP)</span>
                    <div class="text-2xl font-black font-mono text-rose-700">Rs. {{ number_format($supplier->current_balance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRN Consignments Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-3">
        <h3 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-truck-ramp-box text-emerald-600"></i>
            <span>Received Consignments (GRN Invoices)</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3">Date</th>
                        <th class="py-3 px-3">GRN Number</th>
                        <th class="py-3 px-3">Supplier Invoice No</th>
                        <th class="py-3 px-3 text-right">Total Invoiced</th>
                        <th class="py-3 px-3 text-right">Paid Amount</th>
                        <th class="py-3 px-3 text-right">Balance Due</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($grns as $g)
                    @php $balance = max(0, $g->total_amount - $g->paid_amount); @endphp
                    <tr>
                        <td class="py-3 px-3 font-mono text-slate-500">{{ $g->invoice_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $g->grn_number }}</td>
                        <td class="py-3 px-3 font-mono text-slate-500">{{ $g->invoice_number ?? '—' }}</td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($g->total_amount, 2) }}</td>
                        <td class="py-3 px-3 text-right font-mono text-emerald-700">Rs. {{ number_format($g->paid_amount, 2) }}</td>
                        <td class="py-3 px-3 text-right font-mono font-bold {{ $balance > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rs. {{ number_format($balance, 2) }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $balance == 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $balance == 0 ? 'PAID' : 'PENDING' }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-right">
                            <a href="{{ route('grn.show', $g->id) }}" class="text-emerald-600 hover:underline font-semibold">View &rarr;</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-400">No GRN consignments recorded for this supplier.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $grns->withQueryString()->links() }}
        </div>
    </div>

    <!-- Payment Register (Disbursements) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-3">
        <h3 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-money-check text-emerald-600"></i>
            <span>Disbursement & Payouts Ledger</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3 text-right">Amount Paid</th>
                        <th class="py-2.5 px-3">Method</th>
                        <th class="py-2.5 px-3">Ref / Cheque No</th>
                        <th class="py-2.5 px-3">Linked GRN</th>
                        <th class="py-2.5 px-3">Authorized By</th>
                        <th class="py-2.5 px-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($payments as $p)
                    <tr>
                        <td class="py-2.5 px-3 font-mono text-slate-500">{{ $p->payment_date->format('d/m/Y') }}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">Rs. {{ number_format($p->amount_paid, 2) }}</td>
                        <td class="py-2.5 px-3 font-semibold">{{ $p->payment_method }}</td>
                        <td class="py-2.5 px-3 font-mono text-slate-600">{{ $p->reference_number ?? '—' }}</td>
                        <td class="py-2.5 px-3 font-mono">{{ $p->grn ? $p->grn->grn_number : 'General Balance' }}</td>
                        <td class="py-2.5 px-3 text-slate-600">{{ $p->recorder->name ?? 'Admin' }}</td>
                        <td class="py-2.5 px-3 text-slate-500 italic">{{ $p->notes ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-400">No payouts registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $payments->withQueryString()->links() }}
        </div>
    </div>

    <!-- MODAL: Payment Registration Form -->
    <div x-show="payModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="payModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-money-check-dollar text-emerald-600"></i>
                    <span>Register Supplier Disbursement</span>
                </h3>
                <button @click="payModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('suppliers.pay', $supplier->id) }}" class="space-y-4 mt-4 text-xs">
                @csrf

                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Current Outstanding Liability</span>
                    <div class="text-xl font-black font-mono text-rose-700 mt-0.5">Rs. {{ number_format($supplier->current_balance, 2) }}</div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount to Pay (Rs.) *</label>
                    <input type="number" name="amount_paid" step="0.01" min="0.01" required
                           value="{{ $supplier->current_balance > 0 ? $supplier->current_balance : '' }}"
                           class="w-full px-3 py-2 border rounded-xl font-mono text-base font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Method *</label>
                        <select name="payment_method" required class="w-full px-3 py-2 border rounded-xl font-semibold">
                            <option value="Cash">Cash Voucher</option>
                            <option value="Cheque">Bank Cheque</option>
                            <option value="Bank Transfer">Direct Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Payment Date *</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border rounded-xl">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Reference / Cheque Number</label>
                    <input type="text" name="reference_number" placeholder="e.g. CHQ-991823 or Transfer Ref" class="w-full px-3 py-2 border rounded-xl font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Allocate to Specific GRN (Optional)</label>
                    <select name="grn_id" x-model="selectedGrnId" class="w-full px-3 py-2 border rounded-xl">
                        <option value="">-- General Supplier Balance Credit --</option>
                        @foreach($pendingGrns as $pg)
                        <option value="{{ $pg->id }}">{{ $pg->grn_number }} (Due: Rs. {{ number_format($pg->total_amount - $pg->paid_amount, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Payment Notes / Remarks</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Paid per invoice statement..." class="w-full px-3 py-2 border rounded-xl"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="payModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">
                        Confirm Payout
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
