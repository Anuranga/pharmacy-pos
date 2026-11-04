@extends('layouts.app')

@section('title', 'Executive & Clinical Operations Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-5" x-data="{ activeAlertTab: '{{ $negativeStockProducts->isNotEmpty() ? 'reorder' : 'expiry' }}' }">

    <!-- Top Executive Command Header -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 rounded-3xl p-5 md:p-6 text-white shadow-xl border border-slate-800 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-mono text-emerald-400 uppercase tracking-widest">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>PharmaCare Command Center &bull; {{ now()->format('l, d F Y') }}</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold font-display tracking-tight mt-1">
                Welcome back, {{ auth()->user()->name }}
            </h1>
            <p class="text-xs text-slate-300 mt-0.5">
                Real-time pharmacy sales, FEFO batch expiry radar, negative/reorder stock alerts, and financial ledger overview.
            </p>
        </div>

        <!-- 1-Click Quick Action Launchers -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs tracking-wide shadow-lg shadow-emerald-500/25 flex items-center gap-2 transition-all">
                <i class="fa-solid fa-cash-register text-sm"></i>
                <span>OPEN POS COUNTER</span>
            </a>

            @if(auth()->user()->isAdmin())
            <a href="{{ route('grn.create') }}"
               class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/15 font-semibold text-xs flex items-center gap-2 transition-all">
                <i class="fa-solid fa-truck-ramp-box text-teal-300"></i>
                <span>New GRN</span>
            </a>

            <a href="{{ route('master.products.create') }}"
               class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/15 font-semibold text-xs flex items-center gap-2 transition-all">
                <i class="fa-solid fa-pills text-emerald-300"></i>
                <span>+ New Drug</span>
            </a>
            @endif

            <a href="{{ route('reports.eod-z-report') }}"
               class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/15 font-semibold text-xs flex items-center gap-2 transition-all">
                <i class="fa-solid fa-calculator text-amber-300"></i>
                <span>EOD Z-Report</span>
            </a>
        </div>
    </div>

    <!-- Primary 6 KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">

        <!-- 1. Today's Sales Revenue -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Today's Revenue</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="my-2">
                <div class="text-xl font-black font-display text-slate-900 font-mono">Rs. {{ number_format($todayRevenue, 2) }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                    <strong class="text-slate-800">{{ $todayBillsCount }}</strong> Invoices Billed Today
                </div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">vs Yesterday:</span>
                <span class="font-mono font-bold {{ $revenueGrowth >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $revenueGrowth >= 0 ? '+' : '' }}{{ $revenueGrowth }}%
                </span>
            </div>
        </div>

        <!-- 2. Expected Cash in Drawer -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Cash in Drawer</span>
                <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
            </div>
            <div class="my-2">
                <div class="text-xl font-black font-display text-teal-700 font-mono">Rs. {{ number_format($expectedDrawerCash, 2) }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                    Sales: Rs. {{ number_format($todayCashSales, 0) }} | Debt Recv: Rs. {{ number_format($todayDebtCashCollected, 0) }}
                </div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Card / QR Today:</span>
                <span class="font-mono font-bold text-slate-700">Rs. {{ number_format($todayCardSales + $todayQrSales, 0) }}</span>
            </div>
        </div>

        <!-- 3. Monthly Revenue & Gross Profit -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Month Revenue</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="my-2">
                <div class="text-xl font-black font-display text-slate-900 font-mono">Rs. {{ number_format($monthRevenue, 2) }}</div>
                <div class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                    Est. Profit: Rs. {{ number_format($monthGrossProfit, 0) }}
                </div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Gross Margin:</span>
                <span class="font-mono font-bold text-indigo-600">{{ $monthMarginPercent }}% ({{ $monthBillsCount }} bills)</span>
            </div>
        </div>

        <!-- 4. Customer Receivables (Lending) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Customer Debt</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-handshake"></i>
                </div>
            </div>
            <div class="my-2">
                <div class="text-xl font-black font-display text-purple-700 font-mono">Rs. {{ number_format($totalReceivables, 2) }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                    Across <strong class="text-slate-800">{{ $activeDebtorsCount }}</strong> Active Debtors
                </div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Today Lending:</span>
                <a href="{{ route('customers.index') }}" class="font-mono font-bold text-purple-600 hover:underline">
                    Rs. {{ number_format($todayCreditSales, 0) }} &rarr;
                </a>
            </div>
        </div>

        <!-- 5. Supplier Accounts Payable -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Supplier Payable</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-truck-field"></i>
                </div>
            </div>
            <div class="my-2">
                <div class="text-xl font-black font-display text-amber-700 font-mono">Rs. {{ number_format($totalPayables, 2) }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                    Owed to <strong class="text-slate-800">{{ $activeSuppliersCount }}</strong> Suppliers
                </div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Manage Payouts:</span>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('suppliers.index') }}" class="font-bold text-amber-700 hover:underline">Ledger &rarr;</a>
                @else
                <span class="text-slate-400">Admin Only</span>
                @endif
            </div>
        </div>

        <!-- 6. Stock Asset Valuation & Expiry Radar -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Stock Valuation</span>
                <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="my-2">
                <div class="text-xl font-black font-display text-slate-900 font-mono">Rs. {{ number_format($inventoryCostValue, 0) }}</div>
                <div class="text-[11px] text-sky-700 font-semibold mt-0.5">
                    Retail Value: Rs. {{ number_format($inventoryRetailValue, 0) }}
                </div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Catalog Size:</span>
                <span class="font-mono font-bold text-slate-700">{{ number_format($totalProductsCount) }} Drugs</span>
            </div>
        </div>

    </div>

    <!-- Middle Row: 7-Day Revenue Trend Chart (Col 7) & Today's Payment Breakdown + Quick Expiry Summary (Col 5) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        <!-- 7-Day Sales Performance Bar Chart -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-chart-column text-emerald-600"></i>
                        <span>Last 7 Days Revenue & Billing Trend</span>
                    </h3>
                    <p class="text-[11px] text-slate-500">Daily completed counter sales volume and invoice count</p>
                </div>
                <div class="text-right font-mono text-xs">
                    <span class="text-slate-400">7-Day Total:</span>
                    <strong class="text-emerald-700 ml-1">Rs. {{ number_format($last7Days->sum('revenue'), 2) }}</strong>
                </div>
            </div>

            <!-- Interactive CSS Bar Chart -->
            <div class="grid grid-cols-7 gap-2.5 items-end h-44 pt-6 px-2 border-b border-slate-200">
                @foreach($last7Days as $dayStat)
                @php
                    $heightPct = $maxDailyRevenue > 0 ? max(6, round(($dayStat['revenue'] / $maxDailyRevenue) * 100)) : 6;
                    $isToday = $loop->last;
                @endphp
                <div class="flex flex-col items-center h-full justify-end group relative">
                    <!-- Hover Tooltip -->
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-6 bg-slate-900 text-white text-[10px] font-mono px-2 py-0.5 rounded shadow-md whitespace-nowrap z-10 pointer-events-none">
                        Rs. {{ number_format($dayStat['revenue'], 0) }} ({{ $dayStat['bills'] }} bills)
                    </div>

                    <div class="text-[10px] font-mono font-bold mb-1 {{ $isToday ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $dayStat['revenue'] > 0 ? number_format($dayStat['revenue'] / 1000, 1) . 'k' : '0' }}
                    </div>

                    <div class="w-full max-w-[42px] rounded-t-xl transition-all duration-300 {{ $isToday ? 'bg-gradient-to-t from-emerald-600 to-teal-400 shadow-md shadow-emerald-500/20' : 'bg-slate-200 group-hover:bg-emerald-400' }}"
                         style="height: {{ $heightPct }}%;"></div>
                </div>
                @endforeach
            </div>

            <!-- X-Axis Labels -->
            <div class="grid grid-cols-7 gap-2.5 pt-2 text-center">
                @foreach($last7Days as $dayStat)
                <div class="text-[10px]">
                    <div class="font-bold {{ $loop->last ? 'text-emerald-700' : 'text-slate-700' }}">{{ $dayStat['day'] }}</div>
                    <div class="text-slate-400 font-mono text-[9px]">{{ $dayStat['date'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Today's Payment Mode Mix & Expiry Alert Summary Pills -->
        <div class="lg:col-span-5 bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs flex flex-col justify-between space-y-4">
            <div>
                <h3 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-teal-600"></i>
                    <span>Today's Tender Breakdown & FEFO Radar</span>
                </h3>
                <p class="text-[11px] text-slate-500">Live split across payment channels and batch shelf-life status</p>
            </div>

            @php
                $totalTodayTender = max(1, $todayRevenue);
                $cashPct = round(($todayCashSales / $totalTodayTender) * 100);
                $cardPct = round(($todayCardSales / $totalTodayTender) * 100);
                $qrPct = round(($todayQrSales / $totalTodayTender) * 100);
                $creditPct = round(($todayCreditSales / $totalTodayTender) * 100);
            @endphp

            <div class="space-y-2.5 text-xs">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="font-semibold text-slate-700"><i class="fa-solid fa-money-bill-wave text-emerald-600 mr-1.5"></i>Cash Counter</span>
                        <span class="font-mono font-bold text-slate-900">Rs. {{ number_format($todayCashSales, 2) }} ({{ $todayRevenue > 0 ? $cashPct : 0 }}%)</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $todayRevenue > 0 ? $cashPct : 0 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between mb-1">
                        <span class="font-semibold text-slate-700"><i class="fa-solid fa-credit-card text-sky-600 mr-1.5"></i>Card Terminal (POS)</span>
                        <span class="font-mono font-bold text-slate-900">Rs. {{ number_format($todayCardSales, 2) }} ({{ $todayRevenue > 0 ? $cardPct : 0 }}%)</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-sky-500 rounded-full" style="width: {{ $todayRevenue > 0 ? $cardPct : 0 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between mb-1">
                        <span class="font-semibold text-slate-700"><i class="fa-solid fa-qrcode text-teal-600 mr-1.5"></i>LankaQR</span>
                        <span class="font-mono font-bold text-slate-900">Rs. {{ number_format($todayQrSales, 2) }} ({{ $todayRevenue > 0 ? $qrPct : 0 }}%)</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-teal-500 rounded-full" style="width: {{ $todayRevenue > 0 ? $qrPct : 0 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between mb-1">
                        <span class="font-semibold text-slate-700"><i class="fa-solid fa-handshake text-purple-600 mr-1.5"></i>Credit / Lending</span>
                        <span class="font-mono font-bold text-slate-900">Rs. {{ number_format($todayCreditSales, 2) }} ({{ $todayRevenue > 0 ? $creditPct : 0 }}%)</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 rounded-full" style="width: {{ $todayRevenue > 0 ? $creditPct : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- FEFO Expiry Quick Badges -->
            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-center">
                <div class="p-2 rounded-xl bg-rose-50 border border-rose-200">
                    <div class="text-base font-black font-mono text-rose-700">{{ $expiryCounts['expired'] }}</div>
                    <div class="text-[10px] font-bold text-rose-600 uppercase">Expired Batches</div>
                </div>
                <div class="p-2 rounded-xl bg-orange-50 border border-orange-200">
                    <div class="text-base font-black font-mono text-orange-700">{{ $expiryCounts['critical'] }}</div>
                    <div class="text-[10px] font-bold text-orange-600 uppercase">Critical &le; 30d</div>
                </div>
                <div class="p-2 rounded-xl bg-amber-50 border border-amber-200">
                    <div class="text-base font-black font-mono text-amber-700">{{ $expiryCounts['near'] }}</div>
                    <div class="text-[10px] font-bold text-amber-600 uppercase">Near &le; 90d</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Clinical & Inventory Action Center (Tabbed: Reorder/Negative Stock | FEFO Expiry Radar | Top Fast Movers) -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-bell text-emerald-600"></i>
                <h3 class="font-display font-bold text-sm text-slate-900">Pharmacist Operational Action Center</h3>
            </div>

            <div class="flex items-center gap-1.5 text-xs">
                <button type="button"
                        @click="activeAlertTab = 'reorder'"
                        :class="activeAlertTab === 'reorder' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-trend-down text-rose-500"></i>
                    <span>Negative & Low Stock</span>
                    @if($negativeStockProducts->count() > 0)
                    <span class="px-1.5 py-0.2 rounded-full bg-rose-500 text-white text-[10px] font-mono">{{ $negativeStockProducts->count() }} neg</span>
                    @endif
                </button>

                <button type="button"
                        @click="activeAlertTab = 'expiry'"
                        :class="activeAlertTab === 'expiry' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-hourglass-half text-amber-500"></i>
                    <span>FEFO Expiry Watch ({{ $expiryCounts['expired'] + $expiryCounts['critical'] + $expiryCounts['near'] }})</span>
                </button>

                <button type="button"
                        @click="activeAlertTab = 'fastmovers'"
                        :class="activeAlertTab === 'fastmovers' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-fire text-emerald-500"></i>
                    <span>Top Selling Drugs</span>
                </button>
            </div>
        </div>

        <!-- TAB 1: Negative Stock & Low Stock Reorder Watchlist -->
        <div x-show="activeAlertTab === 'reorder'" x-cloak class="p-4">
            @if($negativeStockProducts->isNotEmpty())
            <div class="mb-4 p-3 rounded-2xl bg-rose-50/70 border border-rose-200">
                <div class="text-xs font-bold text-rose-800 mb-2 flex items-center justify-between">
                    <span><i class="fa-solid fa-circle-exclamation mr-1"></i> Negative Stock Balances (Billed Out-of-Stock — Receive GRN to Reconcile)</span>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('grn.create') }}" class="text-rose-700 underline hover:text-rose-900">+ Receive Stock via GRN</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="text-rose-700 font-bold uppercase text-[10px] border-b border-rose-200">
                            <tr>
                                <th class="py-1.5 px-2">Medication</th>
                                <th class="py-1.5 px-2">Pack Size</th>
                                <th class="py-1.5 px-2 text-center">Current Balance</th>
                                <th class="py-1.5 px-2 text-right">Retail Price</th>
                                <th class="py-1.5 px-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-100">
                            @foreach($negativeStockProducts as $negProd)
                            <tr>
                                <td class="py-2 px-2 font-bold text-slate-900">{{ $negProd->brand_name }}</td>
                                <td class="py-2 px-2 font-mono text-[11px] text-slate-600">1 Pack = {{ $negProd->units_per_pack ?? 1 }} {{ $negProd->base_uom }}</td>
                                <td class="py-2 px-2 text-center">
                                    <span class="px-2 py-0.5 rounded-lg bg-rose-600 text-white font-mono font-bold text-xs">
                                        {{ (int) $negProd->total_stock_sum }} {{ $negProd->base_uom }}
                                    </span>
                                </td>
                                <td class="py-2 px-2 text-right font-mono">Rs. {{ number_format($negProd->unit_retail_price, 2) }}</td>
                                <td class="py-2 px-2 text-right">
                                    @if(auth()->user()->isAdmin())
                                    <a href="{{ route('master.products.show', $negProd->id) }}" class="text-rose-700 font-bold hover:underline">Inspect &rarr;</a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">SKU</th>
                            <th class="py-2.5 px-3">Medication</th>
                            <th class="py-2.5 px-3">Category</th>
                            <th class="py-2.5 px-3 text-center">Pack Size</th>
                            <th class="py-2.5 px-3 text-center">Current Stock</th>
                            <th class="py-2.5 px-3 text-center">Reorder Min</th>
                            <th class="py-2.5 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($lowStockProducts as $lp)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 px-3 font-mono text-slate-500">{{ $lp->barcode_sku }}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">{{ $lp->brand_name }}</td>
                            <td class="py-2.5 px-3 text-slate-600">{{ $lp->category->name ?? 'General' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono text-[11px]">{{ $lp->units_per_pack ?? 1 }} {{ $lp->base_uom }}</td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="px-2 py-0.5 rounded font-mono font-bold {{ (int)$lp->total_stock_sum <= 0 ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ (int) $lp->total_stock_sum }} {{ $lp->base_uom }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono text-slate-500">{{ $lp->reorder_min }}</td>
                            <td class="py-2.5 px-3 text-right">
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('master.products.show', $lp->id) }}" class="text-emerald-700 font-bold hover:underline">View Batches &rarr;</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">All active medications are above minimum reorder thresholds.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: FEFO Expiry Watch -->
        <div x-show="activeAlertTab === 'expiry'" x-cloak class="p-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">Medication</th>
                            <th class="py-2.5 px-3 font-mono">Batch No</th>
                            <th class="py-2.5 px-3 font-mono">Expiry Date</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-center">Batch Stock</th>
                            <th class="py-2.5 px-3 text-right">Cost Value at Risk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($urgentExpiryBatches as $eb)
                        @php $st = $eb->expiry_status; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 px-3 font-bold text-slate-900">{{ $eb->product->brand_name ?? 'Unknown' }}</td>
                            <td class="py-2.5 px-3 font-mono font-semibold text-slate-700">{{ $eb->batch_number }}</td>
                            <td class="py-2.5 px-3 font-mono">{{ $eb->expiry_date->format('d M Y') }}</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $st['class'] }}">
                                    {{ $st['status'] }} ({{ $st['badge'] }})
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold">{{ $eb->stock_quantity }} {{ $eb->product->base_uom ?? 'Units' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">
                                Rs. {{ number_format($eb->stock_quantity * $eb->purchase_cost, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No batches expiring within the next 90 days.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: Fast-Moving Medications -->
        <div x-show="activeAlertTab === 'fastmovers'" x-cloak class="p-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3">Rank</th>
                            <th class="py-2.5 px-3">Medication</th>
                            <th class="py-2.5 px-3 text-center">Total Units Sold</th>
                            <th class="py-2.5 px-3 text-right">Total Revenue Generated</th>
                            <th class="py-2.5 px-3 text-center">Live Stock Remaining</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topSellingProducts as $idx => $topItem)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 px-3 font-mono font-bold text-emerald-700">#{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">{{ $topItem->product->brand_name ?? 'Deleted Item' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold">{{ number_format($topItem->total_qty_sold) }} {{ $topItem->product->base_uom ?? 'Units' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">Rs. {{ number_format($topItem->total_revenue, 2) }}</td>
                            <td class="py-2.5 px-3 text-center font-mono">
                                {{ $topItem->product ? $topItem->product->total_stock . ' ' . $topItem->product->base_uom : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">No sales recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bottom Grid: Recent POS Counter Bills (Col 7) & Top Customer Debtors (Col 5) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        <!-- Recent POS Counter Invoices -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-emerald-600"></i>
                        <span>Recent Counter Invoices</span>
                    </h3>
                    <p class="text-[11px] text-slate-500">Latest POS transactions with instant thermal receipt reprint</p>
                </div>
                <a href="{{ route('pos.index') }}" class="text-xs font-bold text-emerald-600 hover:underline">Go to Counter &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2 px-2.5">Invoice #</th>
                            <th class="py-2 px-2.5">Time</th>
                            <th class="py-2 px-2.5">Customer</th>
                            <th class="py-2 px-2.5 text-center">Mode</th>
                            <th class="py-2 px-2.5 text-right">Grand Total</th>
                            <th class="py-2 px-2.5 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentSales as $sale)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-2.5 font-mono font-bold text-slate-900">{{ $sale->invoice_number }}</td>
                            <td class="py-2 px-2.5 font-mono text-[11px] text-slate-500">{{ $sale->created_at->format('d/m H:i') }}</td>
                            <td class="py-2 px-2.5 font-medium text-slate-700">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                            <td class="py-2 px-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $sale->payment_type === 'credit_lending' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ str_replace('_', ' ', $sale->payment_type) }}
                                </span>
                            </td>
                            <td class="py-2 px-2.5 text-right font-mono font-bold text-slate-900">Rs. {{ number_format($sale->grand_total, 2) }}</td>
                            <td class="py-2 px-2.5 text-right">
                                <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank"
                                   class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 font-semibold text-[11px] transition-colors inline-flex items-center gap-1">
                                    <i class="fa-solid fa-print text-[10px]"></i> Print
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No invoices billed yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Customer Debtor Watchlist -->
        <div class="lg:col-span-5 bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-display font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-user-clock text-purple-600"></i>
                        <span>Customer Lending Watchlist</span>
                    </h3>
                    <p class="text-[11px] text-slate-500">Highest outstanding debtor balances & credit limit usage</p>
                </div>
                <a href="{{ route('customers.index') }}" class="text-xs font-bold text-purple-600 hover:underline">All Debtors &rarr;</a>
            </div>

            <div class="space-y-2.5">
                @forelse($topDebtors as $debtor)
                @php
                    $limit = max(1, (float) $debtor->credit_limit);
                    $usedPct = min(100, round(((float) $debtor->current_debt_balance / $limit) * 100));
                @endphp
                <a href="{{ route('customers.show', $debtor->id) }}" class="block p-2.5 rounded-2xl bg-slate-50 hover:bg-purple-50/50 border border-slate-200/80 transition-colors">
                    <div class="flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900">{{ $debtor->name }}</span>
                            <span class="text-[10px] font-mono text-slate-500 ml-1">({{ $debtor->phone }})</span>
                        </div>
                        <span class="font-mono font-bold text-purple-700">Rs. {{ number_format($debtor->current_debt_balance, 2) }}</span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $usedPct >= 85 ? 'bg-rose-500' : 'bg-purple-500' }}" style="width: {{ $usedPct }}%"></div>
                        </div>
                        <span class="text-[10px] font-mono text-slate-500">{{ $usedPct }}% of Rs. {{ number_format($debtor->credit_limit, 0) }}</span>
                    </div>
                </a>
                @empty
                <div class="py-8 text-center text-xs text-slate-400">
                    No outstanding customer lending balances.
                </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection

