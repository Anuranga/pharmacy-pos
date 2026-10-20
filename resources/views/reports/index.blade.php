@extends('layouts.app')

@section('title', 'Financial, Clinical & Inventory Reports Hub')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    
    <div>
        <h1 class="text-3xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-3">
            <i class="fa-solid fa-chart-line text-emerald-600"></i>
            <span>Executive Reporting & Regulatory Audit Hub</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Real-time analytical dashboards covering cashier register closure, financial profit & loss, customer debtor aging, and regulatory prescription tracking.</p>
    </div>

    <!-- Section 1: Sales, Register & Clinical Reports -->
    <div class="space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">1. Counter Sales & Cashier Operations</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <a href="{{ route('reports.eod-z-report') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-emerald-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-emerald-700 text-sm">11. EOD Z-Report (Register Closure)</h3>
                    <p class="text-xs text-slate-500 mt-1">End-of-day register closure report reconciling cash, cards, credit sales, debt collections, and expected drawer totals.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-emerald-600">
                    <span>Reconcile Drawer</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.sales-by-customer') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-amber-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-amber-700 text-sm">5. Sales by Customer</h3>
                    <p class="text-xs text-slate-500 mt-1">Revenue contribution rankings, transaction volumes, and average order values across registered patients and walk-ins.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-amber-600">
                    <span>View Rankings</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.patient-rx-history') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-cyan-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-file-prescription"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-cyan-700 text-sm">7. Patient Rx & Controlled Drug Log</h3>
                    <p class="text-xs text-slate-500 mt-1">Regulatory tracking of prescription-only (Rx) and Form-D controlled dangerous drugs by doctor SLMC and patient name.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-cyan-600">
                    <span>Regulatory Log</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

        </div>
    </div>

    <!-- Section 2: Receivables, Lending & Debtors -->
    <div class="space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">2. Accounts Receivable & Lending Ledgers</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <a href="{{ route('reports.customer-aging') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-rose-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-rose-700 text-sm">3. Customer A/R Aging</h3>
                    <p class="text-xs text-slate-500 mt-1">Accounts Receivable breakdown across 0-30, 31-60, 61-90, and 90+ days overdue buckets.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-rose-600">
                    <span>Analyze Aging</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.customer-balance-summary') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-blue-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-blue-700 text-sm">4. Customer Balance Summary</h3>
                    <p class="text-xs text-slate-500 mt-1">Consolidated debt balances of all registered debtor accounts vs approved credit lines.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                    <span>Balance Roster</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.customer-vs-transaction') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-teal-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-teal-700 text-sm">1. Customer vs Transaction</h3>
                    <p class="text-xs text-slate-500 mt-1">Drill down into sales invoices, line items, and payment modes per customer account.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-teal-600">
                    <span>Transaction Audit</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.customer-extra-payments') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-emerald-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-emerald-700 text-sm">6. Customer Extra Payments</h3>
                    <p class="text-xs text-slate-500 mt-1">Audit log of customer advance deposits and unallocated credit balances.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-emerald-600">
                    <span>Advance Credits</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

        </div>
    </div>

    <!-- Section 3: Financial Margins & Inventory Valuation -->
    <div class="space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">3. Financial Ledger & Inventory Assets</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <a href="{{ route('reports.profit-loss') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-emerald-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-emerald-700 text-sm">10. Profit & Loss Statement</h3>
                    <p class="text-xs text-slate-500 mt-1">Net margin statement factoring in gross revenue, COGS, discounts, and inventory write-offs.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-emerald-600">
                    <span>P&L Statement</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.expiry-alerts') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-amber-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-amber-700 text-sm">9. FEFO Expiry Alerts</h3>
                    <p class="text-xs text-slate-500 mt-1">Tri-color dashboard for Expired (Red), Critical &le; 30d (Orange), and Near Expiry &le; 90d (Yellow).</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-amber-600">
                    <span>Monitor Shelf Life</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.inventory-valuation') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-indigo-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-indigo-700 text-sm">8. Inventory Valuation</h3>
                    <p class="text-xs text-slate-500 mt-1">Asset valuation comparing total purchase cost vs projected retail sales revenue.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-indigo-600">
                    <span>Stock Asset Value</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

            <a href="{{ route('reports.supplier-vs-transaction') }}" class="group bg-white p-5 rounded-3xl border border-slate-200/90 hover:border-sky-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg mb-3 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-truck-moving"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 group-hover:text-sky-700 text-sm">2. Supplier vs Transaction</h3>
                    <p class="text-xs text-slate-500 mt-1">Comprehensive view of GRNs, purchase orders, invoices, and disbursement records.</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-sky-600">
                    <span>Supplier Audit</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
            </a>

        </div>
    </div>

</div>
@endsection
