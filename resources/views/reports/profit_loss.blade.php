@extends('layouts.app')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-emerald-600"></i>
                <span>10. Profit & Loss Statement (P&L)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Net profit margin analysis factoring in gross revenues, Cost of Goods Sold (COGS), discounts, and inventory write-offs.</p>
        </div>

        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Reports Hub
        </a>
    </div>

    <!-- Date Range Picker -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.profit-loss') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs items-end">
            <div class="md:col-span-5">
                <label class="block font-semibold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>
            <div class="md:col-span-5">
                <label class="block font-semibold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 bg-slate-50 border rounded-xl">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-colors">
                    Generate P&L
                </button>
            </div>
        </form>
    </div>

    <!-- P&L Financial Ledger Statement Card -->
    <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm space-y-6">
        
        <div class="text-center pb-6 border-b border-slate-200">
            <span class="text-xs uppercase font-mono font-bold tracking-widest text-emerald-600">Executive Financial Audit</span>
            <h2 class="text-2xl font-black font-display text-slate-900 tracking-tight mt-1">PharmaCare Statement of Operations</h2>
            <p class="text-xs text-slate-500 mt-0.5">Reporting Period: <strong class="text-slate-800">{{ date('d M Y', strtotime($startDate)) }}</strong> to <strong class="text-slate-800">{{ date('d M Y', strtotime($endDate)) }}</strong></p>
        </div>

        <!-- Ledger Line Items -->
        <div class="space-y-4 text-xs font-medium">
            
            <!-- Revenue Section -->
            <div class="space-y-2">
                <div class="font-bold uppercase tracking-wider text-slate-400 text-[11px]">1. Operating Sales Revenue</div>
                <div class="flex justify-between items-center py-2 px-3 bg-slate-50 rounded-xl">
                    <span class="text-slate-700 font-semibold">Gross Counter Sales</span>
                    <span class="font-mono font-bold text-slate-900">Rs. {{ number_format($grossSales, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 px-3 text-slate-600">
                    <span>Less: Promotional Discounts Allowed</span>
                    <span class="font-mono text-rose-600 font-bold">- Rs. {{ number_format($totalDiscounts, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 px-3 bg-slate-100 rounded-xl font-bold text-slate-900">
                    <span>Net Sales Revenue</span>
                    <span class="font-mono text-sm">Rs. {{ number_format($netSalesRevenue, 2) }}</span>
                </div>
            </div>

            <!-- Cost of Goods Sold Section -->
            <div class="space-y-2 pt-2">
                <div class="font-bold uppercase tracking-wider text-slate-400 text-[11px]">2. Cost of Goods Sold (COGS)</div>
                <div class="flex justify-between items-center py-2 px-3 bg-slate-50 rounded-xl">
                    <span class="text-slate-700 font-semibold">Purchase Acquisition Cost of Sold Medicines</span>
                    <span class="font-mono font-bold text-slate-800">Rs. {{ number_format($totalCogs, 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 px-3 bg-emerald-50 text-emerald-900 rounded-xl font-bold">
                    <span>Gross Profit (Net Sales - COGS)</span>
                    <span class="font-mono text-sm">Rs. {{ number_format($grossProfit, 2) }}</span>
                </div>
            </div>

            <!-- Write-Offs & Losses -->
            <div class="space-y-2 pt-2">
                <div class="font-bold uppercase tracking-wider text-slate-400 text-[11px]">3. Inventory Shrinkage & Write-Offs</div>
                <div class="flex justify-between items-center py-2 px-3 bg-slate-50 rounded-xl">
                    <div>
                        <span class="text-slate-700 font-semibold block">Damaged, Expired & Broken Drug Deductions</span>
                        <span class="text-[10px] text-slate-400 italic">({{ $writeOffs->count() }} audit adjustment write-off events)</span>
                    </div>
                    <span class="font-mono font-bold text-rose-600">- Rs. {{ number_format($totalWriteOffLoss, 2) }}</span>
                </div>
            </div>

            <!-- Net Margin Bottom Line -->
            <div class="pt-4 border-t-2 border-slate-900">
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-5 rounded-2xl flex items-center justify-between">
                    <div>
                        <span class="text-xs uppercase font-bold tracking-widest text-emerald-400">Net Operating Pharmacy Profit</span>
                        <div class="text-xs text-slate-300 mt-0.5">Net Margin Percentage: <strong class="text-emerald-400 font-mono">{{ number_format($netMarginPercent, 2) }}%</strong></div>
                    </div>
                    <div class="text-3xl font-black font-display font-mono text-emerald-400">
                        Rs. {{ number_format($netMargin, 2) }}
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
