@extends('layouts.app')

@section('title', $customer->name . ' - Lending Ledger')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" 
     x-data="{ 
        settleModal: false, 
        extraModal: false,
        editModal: false,
        editForm: {
            name: @js($customer->name),
            phone: @js($customer->phone),
            nic_number: @js($customer->nic_number ?? ''),
            credit_limit: @js($customer->credit_limit),
            address: @js($customer->address ?? '')
        }
     }">
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <a href="{{ route('customers.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Debtors List
        </a>

        <div class="flex items-center gap-2">
            <button type="button" @click="editModal = true"
                    class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-semibold rounded-xl text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                <span>Edit Customer Details</span>
            </button>

            <button type="button" @click="extraModal = true"
                    class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-semibold rounded-xl text-xs shadow-xs transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-wallet text-teal-600"></i>
                <span>Advance Deposit</span>
            </button>

            <button type="button" @click="settleModal = true"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span>Settle Debt Tab</span>
            </button>
        </div>
    </div>

    <!-- Customer Profile & Ledger Overview Card -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold text-emerald-600 uppercase tracking-widest">Debtor Ledger Account</span>
                    <button type="button" @click="editModal = true" class="text-xs text-slate-400 hover:text-emerald-600" title="Edit details">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                </div>
                <h1 class="text-3xl font-extrabold font-display text-slate-900 tracking-tight mt-1">{{ $customer->name }}</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Phone: <strong class="font-mono text-slate-800">{{ $customer->phone }}</strong> | 
                    NIC: <strong class="font-mono text-slate-800">{{ $customer->nic_number ?? 'Not provided' }}</strong>
                </p>
                <p class="text-xs text-slate-500 mt-0.5">Address: {{ $customer->address ?? 'No address registered' }}</p>
            </div>

            <!-- Financial Counters -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-right">
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Total Purchases</span>
                    <div class="text-sm font-bold font-mono text-slate-800">Rs. {{ number_format($summary['total_purchased'], 2) }}</div>
                </div>

                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Approved Limit</span>
                    <div class="text-sm font-bold font-mono text-slate-800">Rs. {{ number_format($customer->credit_limit, 2) }}</div>
                </div>

                <div class="bg-rose-50 p-3 rounded-2xl border border-rose-200">
                    <span class="text-[10px] font-bold text-rose-600 uppercase">Outstanding Debt</span>
                    <div class="text-lg font-black font-mono text-rose-700">Rs. {{ number_format($customer->current_debt_balance, 2) }}</div>
                </div>

                <div class="bg-emerald-50 p-3 rounded-2xl border border-emerald-200">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase">Remaining Credit</span>
                    <div class="text-lg font-black font-mono text-emerald-700">Rs. {{ number_format($customer->remaining_credit, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Overdue Aging Pill Status -->
        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs">
            <span class="font-bold text-slate-700">A/R Aging Breakdown:</span>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                    0-30 Days: Rs. {{ number_format($agingData['current'], 2) }}
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-yellow-50 text-yellow-800 font-semibold border border-yellow-200">
                    31-60 Days: Rs. {{ number_format($agingData['tier_30_60'], 2) }}
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-800 font-semibold border border-orange-200">
                    61-90 Days: Rs. {{ number_format($agingData['tier_60_90'], 2) }}
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-800 font-semibold border border-rose-200">
                    90+ Days: Rs. {{ number_format($agingData['tier_90_plus'], 2) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Sales Invoices Audit & History (with Date Filter) -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-emerald-600"></i>
                    <span>Invoice-by-Invoice Sales Audit History</span>
                </h3>
                <p class="text-xs text-slate-500">Chronological ledger of counter transactions and lent tabs.</p>
            </div>

            <!-- Date Range Filter -->
            <form method="GET" action="{{ route('customers.show', $customer->id) }}" class="flex items-center gap-2 text-xs">
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="px-2.5 py-1.5 border rounded-lg">
                <span class="text-slate-400">to</span>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="px-2.5 py-1.5 border rounded-lg">
                <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition-colors">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3">Date & Time</th>
                        <th class="py-3 px-3">Invoice Number</th>
                        <th class="py-3 px-3">Payment Mode</th>
                        <th class="py-3 px-4 text-right">Invoice Total</th>
                        <th class="py-3 px-4 text-right">Amount Paid</th>
                        <th class="py-3 px-4 text-right">Balance Due</th>
                        <th class="py-3 px-3 text-center">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-3 font-mono text-slate-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $sale->invoice_number }}</td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono {{ $sale->payment_type === 'credit_lending' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $sale->payment_type }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            Rs. {{ number_format($sale->grand_total, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-700">
                            Rs. {{ number_format($sale->amount_paid, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $sale->balance_due > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rs. {{ number_format($sale->balance_due, 2) }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800 font-bold">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No transactions recorded in this date range.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3">
            {{ $sales->withQueryString()->links() }}
        </div>
    </div>

    <!-- Settlement History -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6">
        <h4 class="font-display font-bold text-sm text-slate-900 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-handshake text-emerald-600"></i>
            <span>Customer Settlement Payments Received</span>
        </h4>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3 text-right">Amount Received</th>
                        <th class="py-2.5 px-3">Payment Method</th>
                        <th class="py-2.5 px-3">Received By</th>
                        <th class="py-2.5 px-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($settlements as $set)
                    <tr>
                        <td class="py-2.5 px-3 font-mono text-slate-500">{{ $set->date->format('d/m/Y H:i') }}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">Rs. {{ number_format($set->amount_received, 2) }}</td>
                        <td class="py-2.5 px-3 font-semibold">{{ $set->payment_method }}</td>
                        <td class="py-2.5 px-3 text-slate-600">{{ $set->receiver->name ?? 'Staff' }}</td>
                        <td class="py-2.5 px-3 text-slate-500 italic">{{ $set->notes ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-400">No debt settlements registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Edit Customer Details Modal -->
    <div x-show="editModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="editModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-emerald-600"></i>
                    <span>Edit Customer Profile</span>
                </h3>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('customers.update', $customer->id) }}" class="space-y-3 mt-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Customer Full Name *</label>
                    <input type="text" name="name" x-model="editForm.name" required class="w-full px-3 py-2 border rounded-xl font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone Number *</label>
                        <input type="text" name="phone" x-model="editForm.phone" required class="w-full px-3 py-2 border rounded-xl font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">National ID (NIC)</label>
                        <input type="text" name="nic_number" x-model="editForm.nic_number" class="w-full px-3 py-2 border rounded-xl font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Approved Credit Limit (Rs.) *</label>
                    <input type="number" min="0" step="100" name="credit_limit" x-model.number="editForm.credit_limit" required 
                           class="w-full px-3 py-2 border rounded-xl font-mono font-bold text-emerald-700 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Approved credit cap for counter tab lending.</span>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Residential Address</label>
                    <textarea name="address" x-model="editForm.address" rows="2" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="editModal = false" class="px-4 py-2 border rounded-xl text-slate-600 hover:bg-slate-50 font-medium">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Settle Balance Interface -->
    <div x-show="settleModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="settleModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
                    <span>Settle Outstanding Debt</span>
                </h3>
                <button @click="settleModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('customers.settle', $customer->id) }}" class="space-y-4 mt-4 text-xs">
                @csrf

                <div class="p-3 bg-rose-50 rounded-2xl border border-rose-200 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-rose-600">Total Outstanding Balance</span>
                        <div class="text-xl font-black font-mono text-rose-700 mt-0.5">Rs. {{ number_format($customer->current_debt_balance, 2) }}</div>
                    </div>
                    <button type="button" @click="document.getElementById('settleAmount').value = {{ $customer->current_debt_balance }}"
                            class="px-3 py-1 bg-white border border-rose-300 text-rose-700 font-bold rounded-lg text-xs hover:bg-rose-100">
                        Pay Full
                    </button>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amount Received (Rs.) *</label>
                    <input type="number" id="settleAmount" name="amount_received" step="0.01" min="0.01" max="{{ $customer->current_debt_balance }}" required
                           value="{{ $customer->current_debt_balance }}"
                           class="w-full px-3 py-2 border rounded-xl font-mono text-base font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-3 py-2 border rounded-xl font-semibold">
                        <option value="Cash">Cash at Register</option>
                        <option value="Card">Credit / Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer / Deposit</option>
                        <option value="QR">LankaQR</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Settlement Receipt Notes</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Paid in full by cheque / cash..." class="w-full px-3 py-2 border rounded-xl"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="settleModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">
                        Issue Settlement Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Extra Payment / Advance Deposit Modal -->
    <div x-show="extraModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.outside="extraModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-emerald-600"></i>
                    <span>Log Advance / Extra Payment</span>
                </h3>
                <button @click="extraModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('customers.extra-payment', $customer->id) }}" class="space-y-4 mt-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Advance Amount (Rs.) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="w-full px-3 py-2 border rounded-xl font-mono text-base font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-3 py-2 border rounded-xl font-semibold">
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Card">Card</option>
                        <option value="QR">QR</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Advance for future medicine supplies..." class="w-full px-3 py-2 border rounded-xl"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button type="button" @click="extraModal = false" class="px-4 py-2 border rounded-xl text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm">
                        Record Advance Deposit
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
