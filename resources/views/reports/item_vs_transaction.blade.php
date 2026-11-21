@extends('layouts.app')

@section('title', 'Item vs Transaction (Drug-Wise Sales & GRN Report)')

@section('content')
@php
    $catalogJson = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'brand_name' => $p->brand_name,
            'generic_name' => $p->generic_name ?? '',
            'dosage_strength' => $p->dosage_strength ?? '',
            'barcode_sku' => $p->barcode_sku ?? '',
            'category_name' => $p->category?->name ?? 'General',
            'base_uom' => $p->base_uom ?? 'Unit',
            'unit_cost_price' => (float) $p->unit_cost_price,
            'unit_retail_price' => (float) $p->unit_retail_price,
            'live_stock' => (int) $p->batches->sum('stock_quantity'),
        ];
    })->values();

    $selectedProductJson = $selectedProduct ? [
        'id' => $selectedProduct->id,
        'brand_name' => $selectedProduct->brand_name,
        'generic_name' => $selectedProduct->generic_name ?? '',
        'dosage_strength' => $selectedProduct->dosage_strength ?? '',
        'barcode_sku' => $selectedProduct->barcode_sku ?? '',
        'base_uom' => $selectedProduct->base_uom ?? 'Unit',
    ] : null;
@endphp

<div class="max-w-[1550px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
     x-data="itemVsTransactionApp()">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                    Drug-Wise Sales & GRN Audit
                </span>
            </div>
            <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-capsules text-emerald-600"></i>
                <span>13. Item vs Transaction Report (Drug-Wise Sold & GRN Received)</span>
            </h1>
            <p class="ui-subheading text-xs text-slate-500 mt-0.5">
                Analyze how any selected drug was sold during a given date period with sold amounts & invoice previews, alongside how it was received via GRN on the same page.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- View Layout Switcher -->
            <div class="inline-flex rounded-xl bg-slate-200/80 p-1 text-xs font-bold">
                <button type="button" @click="layoutMode = 'split'"
                        :class="layoutMode === 'split' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-table-columns text-emerald-600"></i>
                    <span>Side-by-Side</span>
                </button>
                <button type="button" @click="layoutMode = 'stacked'"
                        :class="layoutMode === 'stacked' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group text-sky-600"></i>
                    <span>Full-Width Stacked</span>
                </button>
            </div>

            <button type="button" onclick="window.print()"
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold shadow-xs flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-print text-slate-500"></i>
                <span>Print</span>
            </button>

            <a href="{{ route('reports.index') }}"
               class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Reports Hub</span>
            </a>
        </div>
    </div>

    <!-- Search & Date Period Filter Bar -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs space-y-4 no-print">
        <form id="itemReportFilterForm" method="GET" action="{{ route('reports.item-vs-transaction') }}" class="space-y-4">
            <input type="hidden" name="product_id" :value="selectedId">
            <input type="hidden" name="preset" :value="preset">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-end text-xs">
                <!-- Search & Select Drug Autocomplete (Col 5) -->
                <div class="lg:col-span-5 relative" @click.outside="dropdownOpen = false">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px]">
                            <i class="fa-solid fa-magnifying-glass text-emerald-600 mr-1"></i> Search & Select Drug / Item
                        </label>
                        <button type="button"
                                @click="selectAllDrugs()"
                                class="text-[11px] font-bold text-emerald-700 hover:underline cursor-pointer">
                            View All Items
                        </button>
                    </div>

                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @focus="dropdownOpen = true"
                               @input="dropdownOpen = true"
                               placeholder="Type drug brand name, generic name, or barcode SKU..."
                               autocomplete="off"
                               class="w-full pl-9 pr-8 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none font-bold text-slate-900 text-xs">
                        <i class="fa-solid fa-pills text-emerald-600 absolute left-3 top-1/2 -translate-y-1/2 text-xs"></i>

                        <button type="button"
                                x-show="searchQuery.length > 0"
                                @click="clearDrugSearch()"
                                title="Clear search"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600 cursor-pointer">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </button>
                    </div>

                    <!-- Autocomplete Results Dropdown -->
                    <div x-show="dropdownOpen" x-cloak
                         class="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl max-h-72 overflow-y-auto z-50 divide-y divide-slate-100">
                        <div @click="selectAllDrugs()"
                             class="px-3.5 py-2.5 hover:bg-emerald-50 cursor-pointer flex items-center justify-between text-xs font-bold text-slate-700 bg-slate-50/70">
                            <span><i class="fa-solid fa-layer-group text-emerald-600 mr-1.5"></i> -- All Medications / Items --</span>
                            <span class="text-[10px] font-mono text-slate-400">{{ $products->count() }} items</span>
                        </div>

                        <template x-for="prod in filteredProducts" :key="prod.id">
                            <div @click="chooseProduct(prod)"
                                 class="px-3.5 py-2.5 hover:bg-emerald-50/80 cursor-pointer transition-colors flex items-center justify-between gap-2"
                                 :class="selectedId == prod.id ? 'bg-emerald-50/60' : ''">
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900 text-xs truncate">
                                        <span x-text="prod.brand_name"></span>
                                        <span x-show="prod.dosage_strength" class="text-slate-500 font-normal" x-text="'(' + prod.dosage_strength + ')'"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 truncate">
                                        <span x-text="prod.generic_name || prod.category_name"></span>
                                        <span class="font-mono text-slate-400 ml-1" x-text="'• SKU: ' + prod.barcode_sku"></span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                                          :class="prod.live_stock > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700'"
                                          x-text="prod.live_stock + ' ' + prod.base_uom"></span>
                                    <div class="text-[10px] font-mono text-slate-500 mt-0.5" x-text="'Rs. ' + prod.unit_retail_price.toFixed(2)"></div>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredProducts.length === 0" class="p-4 text-center text-xs text-slate-400">
                            No matching drugs found for "<span class="font-bold text-slate-600" x-text="searchQuery"></span>"
                        </div>
                    </div>
                </div>

                <!-- From Date (Col 2) -->
                <div class="lg:col-span-2">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-1">From Date</label>
                    <input type="date" name="start_date" x-model="startDate" @change="preset = ''"
                           class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <!-- To Date (Col 2) -->
                <div class="lg:col-span-2">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-1">To Date</label>
                    <input type="date" name="end_date" x-model="endDate" @change="preset = ''"
                           class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-mono text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500">
                </div>

                <!-- GRN Date Scope (Col 2) -->
                <div class="lg:col-span-2">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-1">GRN History Scope</label>
                    <select name="grn_scope" x-model="grnScope"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl font-semibold text-xs focus:bg-white focus:ring-2 focus:ring-emerald-500">
                        <option value="auto">Smart (Period + History)</option>
                        <option value="period">Selected Dates Only</option>
                        <option value="all">All GRNs for Item</option>
                    </select>
                </div>

                <!-- Submit Filter (Col 1) -->
                <div class="lg:col-span-1">
                    <button type="submit"
                            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-filter"></i>
                        <span>Load</span>
                    </button>
                </div>
            </div>

            <!-- 1-Click Quick Date Period Presets (Today, Yesterday, Last 7 Days, This Month, All Time) -->
            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-1">
                        <i class="fa-regular fa-calendar-check text-emerald-600 mr-1"></i> Quick Period:
                    </span>

                    <button type="button" @click="applyPreset('today')"
                            :class="preset === 'today' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                            class="px-3 py-1 rounded-lg border font-bold text-[11px] transition-colors cursor-pointer">
                        Today
                    </button>

                    <button type="button" @click="applyPreset('yesterday')"
                            :class="preset === 'yesterday' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200'"
                            class="px-3 py-1 rounded-lg border font-bold text-[11px] transition-colors cursor-pointer">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> Yesterday
                    </button>

                    <button type="button" @click="applyPreset('last_7_days')"
                            :class="preset === 'last_7_days' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                            class="px-3 py-1 rounded-lg border font-bold text-[11px] transition-colors cursor-pointer">
                        Last 7 Days
                    </button>

                    <button type="button" @click="applyPreset('this_month')"
                            :class="preset === 'this_month' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                            class="px-3 py-1 rounded-lg border font-bold text-[11px] transition-colors cursor-pointer">
                        This Month
                    </button>

                    <button type="button" @click="applyPreset('all_time')"
                            :class="preset === 'all_time' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'"
                            class="px-3 py-1 rounded-lg border font-bold text-[11px] transition-colors cursor-pointer">
                        All Time
                    </button>
                </div>

                <div class="text-[11px] font-mono text-slate-500">
                    @if($startDate && $endDate)
                        Period: <strong class="text-slate-800">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</strong> &rarr; <strong class="text-slate-800">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
                    @else
                        Period: <strong class="text-slate-800">All Historical Dates</strong>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Selected Drug Profile Banner -->
    @if($selectedProduct)
    @php
        $liveStock = (int) $selectedProduct->batches->sum('stock_quantity');
        $uom = $selectedProduct->base_uom ?? 'Unit';
    @endphp
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 text-white rounded-3xl p-5 shadow-lg flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    SKU: {{ $selectedProduct->barcode_sku }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-white/10 text-slate-200">
                    {{ $selectedProduct->category->name ?? 'General' }}
                </span>
                @if(($selectedProduct->units_per_pack ?? 1) > 1)
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                    1 Pack = {{ $selectedProduct->units_per_pack }} {{ $uom }}
                </span>
                @endif
            </div>
            <h2 class="text-xl md:text-2xl font-black font-display tracking-tight mt-1">
                {{ $selectedProduct->brand_name }}
                @if($selectedProduct->dosage_strength)
                    <span class="text-emerald-300 font-semibold text-base">({{ $selectedProduct->dosage_strength }})</span>
                @endif
            </h2>
            <p class="text-xs text-slate-300 italic">
                Generic: {{ $selectedProduct->generic_name ?: 'Not specified' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-white/10 px-4 py-2.5 rounded-2xl border border-white/10 text-right">
                <span class="text-[10px] uppercase font-bold text-slate-300 block">Unit Cost Price</span>
                <span class="text-base font-black font-mono text-amber-300">Rs. {{ number_format($selectedProduct->unit_cost_price, 2) }}</span>
            </div>
            <div class="bg-white/10 px-4 py-2.5 rounded-2xl border border-white/10 text-right">
                <span class="text-[10px] uppercase font-bold text-slate-300 block">Unit Selling Price</span>
                <span class="text-base font-black font-mono text-emerald-300">Rs. {{ number_format($selectedProduct->unit_retail_price, 2) }}</span>
            </div>
            <div class="bg-white/10 px-4 py-2.5 rounded-2xl border border-white/10 text-right">
                <span class="text-[10px] uppercase font-bold text-slate-300 block">Live Stock in Hand</span>
                <span class="text-base font-black font-mono {{ $liveStock > 0 ? 'text-white' : 'text-rose-400' }}">
                    {{ number_format($liveStock) }} {{ $uom }}
                </span>
            </div>
            <a href="{{ route('master.products.show', $selectedProduct->id) }}"
               class="no-print px-3.5 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white border border-white/15 text-xs font-bold transition-colors">
                <i class="fa-solid fa-boxes-stacked mr-1"></i> Batches
            </a>
        </div>
    </div>
    @endif

    <!-- Period Summary KPI Cards (Sold with Amount vs GRN Received with Amount) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Quantity Sold -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Qty Sold (Period)</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-cart-Flatbed-suitcase fa-bag-shopping"></i>
                </span>
            </div>
            <div class="text-xl font-black font-mono text-slate-900 mt-1.5">
                {{ number_format($salesSummary['total_qty_sold']) }}
                <span class="text-xs font-semibold text-slate-500">{{ $selectedProduct->base_uom ?? 'Units' }}</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Across <strong class="text-slate-800">{{ number_format($salesSummary['invoices_count']) }}</strong> POS Invoices
            </div>
        </div>

        <!-- 2. Total Sold Amount (Revenue) -->
        <div class="bg-emerald-50/70 p-4 rounded-2xl border-2 border-emerald-500/40 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Total Sold Amount</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs">
                    <i class="fa-solid fa-sack-dollar"></i>
                </span>
            </div>
            <div class="text-xl font-black font-mono text-emerald-800 mt-1.5">
                Rs. {{ number_format($salesSummary['total_sold_amount'], 2) }}
            </div>
            <div class="text-[11px] text-emerald-700 font-semibold mt-1">
                Gross Billed Revenue for Item
            </div>
        </div>

        <!-- 3. Estimated Profit on Sold Item -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Est. Margin / Profit</span>
                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-chart-line"></i>
                </span>
            </div>
            <div class="text-xl font-black font-mono {{ $salesSummary['gross_profit'] >= 0 ? 'text-indigo-700' : 'text-rose-600' }} mt-1.5">
                Rs. {{ number_format($salesSummary['gross_profit'], 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1 font-mono">
                COGS: Rs. {{ number_format($salesSummary['total_cogs'], 2) }}
            </div>
        </div>

        <!-- 4. Total GRN Quantity Received -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">GRN Qty Received</span>
                <span class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-boxes-packing"></i>
                </span>
            </div>
            <div class="text-xl font-black font-mono text-slate-900 mt-1.5">
                {{ number_format($grnSummary['total_qty_received']) }}
                @if($grnSummary['total_bonus_received'] > 0)
                    <span class="text-xs font-bold text-emerald-600">+{{ number_format($grnSummary['total_bonus_received']) }} Free</span>
                @endif
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                From <strong class="text-slate-800">{{ number_format($grnSummary['grns_count']) }}</strong> GRN Consignments
            </div>
        </div>

        <!-- 5. Total GRN Purchase Cost -->
        <div class="bg-sky-50/70 p-4 rounded-2xl border-2 border-sky-500/40 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider">GRN Purchase Value</span>
                <span class="w-7 h-7 rounded-lg bg-sky-600 text-white flex items-center justify-center text-xs">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </span>
            </div>
            <div class="text-xl font-black font-mono text-sky-900 mt-1.5">
                Rs. {{ number_format($grnSummary['total_grn_amount'], 2) }}
            </div>
            <div class="text-[11px] text-sky-700 font-semibold mt-1">
                Total Supplier Invoiced Cost
            </div>
        </div>
    </div>

    <!-- SAME-PAGE DUAL TABLES: 1. How Item Was Sold (Invoices) & 2. How Item Was Received (GRNs) -->
    <div :class="layoutMode === 'split' ? 'grid grid-cols-1 xl:grid-cols-2 gap-6 items-start' : 'space-y-6'">

        <!-- TABLE 1: HOW ITEM WAS SOLD (POS SALES INVOICES WITH AMOUNT & INVOICE VIEWER) -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-sm text-white">
                            1. How Item Was Sold — POS Sales Invoices
                        </h3>
                        <p class="text-[11px] text-slate-300 font-mono">
                            {{ number_format($salesSummary['lines_count']) }} sale records &bull; Total Sold: <strong class="text-emerald-400">Rs. {{ number_format($salesSummary['total_sold_amount'], 2) }}</strong>
                        </p>
                    </div>
                </div>

                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono font-bold text-xs">
                    Qty Sold: {{ number_format($salesSummary['total_qty_sold']) }} {{ $selectedProduct->base_uom ?? 'Units' }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                        <tr>
                            <th class="py-3 px-3">Date & Time</th>
                            <th class="py-3 px-3">Invoice #</th>
                            @if(!$selectedProduct)
                            <th class="py-3 px-3">Drug / Item</th>
                            @endif
                            <th class="py-3 px-3">Customer / Mode</th>
                            <th class="py-3 px-3 text-center">Qty Sold</th>
                            <th class="py-3 px-3 text-right">Unit Price</th>
                            <th class="py-3 px-3 text-right">Sold Amount</th>
                            <th class="py-3 px-3 text-center no-print">Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($saleItems as $si)
                        @php
                            $sale = $si->sale;
                            $uomLabel = $si->product->base_uom ?? 'Unit';
                            $invoiceModalPayload = [
                                'id' => $sale->id,
                                'invoice_number' => $sale->invoice_number,
                                'date' => $sale->created_at->format('d M Y, h:i A'),
                                'customer_name' => $sale->customer->name ?? 'Walk-in Customer',
                                'customer_phone' => $sale->customer->phone ?? '',
                                'cashier_name' => $sale->cashier->name ?? 'Cashier',
                                'payment_type' => strtoupper(str_replace('_', ' ', $sale->payment_type)),
                                'subtotal' => (float) $sale->subtotal,
                                'discount_amount' => (float) $sale->discount_amount,
                                'grand_total' => (float) $sale->grand_total,
                                'amount_paid' => (float) $sale->amount_paid,
                                'balance_due' => (float) $sale->balance_due,
                                'change_given' => (float) $sale->change_given,
                                'receipt_url' => route('pos.receipt', $sale->id),
                                'highlight_product_id' => $si->product_id,
                                'items' => $sale->items->map(fn($line) => [
                                    'product_id' => $line->product_id,
                                    'brand_name' => $line->product->brand_name ?? 'Item',
                                    'uom' => $line->product->base_uom ?? 'Unit',
                                    'quantity' => (int) $line->quantity,
                                    'unit_price' => (float) $line->unit_price,
                                    'subtotal' => (float) $line->subtotal,
                                ])->values(),
                            ];
                        @endphp
                        <tr class="hover:bg-emerald-50/40 transition-colors">
                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $sale->created_at->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $sale->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                                <button type="button"
                                        @click="openInvoiceModal({{ json_encode($invoiceModalPayload) }})"
                                        class="text-emerald-700 hover:text-emerald-900 hover:underline cursor-pointer">
                                    {{ $sale->invoice_number }}
                                </button>
                                @if($si->batch)
                                <div class="text-[10px] text-slate-400 font-normal">Batch: {{ $si->batch->batch_number }}</div>
                                @endif
                            </td>
                            @if(!$selectedProduct)
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ $si->product->brand_name ?? 'Deleted Item' }}
                            </td>
                            @endif
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-slate-800 truncate max-w-[140px]">{{ $sale->customer->name ?? 'Walk-in' }}</div>
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold uppercase {{ $sale->payment_type === 'credit_lending' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ str_replace('_', ' ', $sale->payment_type) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono">
                                <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold">
                                    {{ number_format($si->quantity) }} {{ $uomLabel }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-600 whitespace-nowrap">
                                Rs. {{ number_format($si->unit_price, 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-700 whitespace-nowrap">
                                Rs. {{ number_format($si->subtotal, 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap no-print">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            @click="openInvoiceModal({{ json_encode($invoiceModalPayload) }})"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] shadow-xs transition-colors cursor-pointer inline-flex items-center gap-1">
                                        <i class="fa-solid fa-eye text-[10px]"></i> View Invoice
                                    </button>
                                    <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank"
                                       title="Print Thermal Receipt"
                                       class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-800 text-slate-600 hover:text-white transition-colors">
                                        <i class="fa-solid fa-print text-[11px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $selectedProduct ? 7 : 8 }}" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-receipt text-2xl text-slate-300 mb-2 block"></i>
                                No POS sales recorded for this item in the selected date period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($saleItems->count() > 0)
                    <tfoot class="bg-emerald-50/70 border-t-2 border-emerald-200 font-mono text-xs font-bold text-slate-900">
                        <tr>
                            <td colspan="{{ $selectedProduct ? 3 : 4 }}" class="py-3 px-3 text-right uppercase text-[10px] tracking-wider text-slate-600">
                                Period Sold Total:
                            </td>
                            <td class="py-3 px-3 text-center text-emerald-800">
                                {{ number_format($salesSummary['total_qty_sold']) }} {{ $selectedProduct->base_uom ?? 'Units' }}
                            </td>
                            <td class="py-3 px-3"></td>
                            <td class="py-3 px-3 text-right text-emerald-800 text-sm font-black">
                                Rs. {{ number_format($salesSummary['total_sold_amount'], 2) }}
                            </td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            @if($saleItems->hasPages())
            <div class="p-4 border-t border-slate-200 no-print">
                {{ $saleItems->withQueryString()->links() }}
            </div>
            @endif
        </div>

        <!-- TABLE 2: HOW ITEM WAS RECEIVED (GRN CONSIGNMENTS WITH SUPPLIER, BATCH, COST & GRN VIEWER) -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-sm text-white">
                            2. How Item Was Received — GRN Procurement History
                        </h3>
                        <p class="text-[11px] text-slate-300 font-mono">
                            {{ number_format($grnSummary['grns_count']) }} GRNs &bull; Purchase Value: <strong class="text-sky-400">Rs. {{ number_format($grnSummary['total_grn_amount'], 2) }}</strong>
                        </p>
                    </div>
                </div>

                <span class="px-2.5 py-1 rounded-lg bg-sky-500/20 text-sky-300 border border-sky-500/30 font-mono font-bold text-xs">
                    Received: {{ number_format($grnSummary['total_qty_received']) }}
                    @if($grnSummary['total_bonus_received'] > 0)
                        + {{ number_format($grnSummary['total_bonus_received']) }} Bonus
                    @endif
                </span>
            </div>

            @if($showingHistoricalGrnsFallback)
            <div class="px-4 py-2.5 bg-amber-50 border-b border-amber-200 text-amber-900 text-xs flex items-center justify-between">
                <span>
                    <i class="fa-solid fa-circle-info text-amber-600 mr-1"></i>
                    No GRN was received between <strong>{{ $startDate }}</strong> and <strong>{{ $endDate }}</strong> — showing historical GRNs below so you can see how this item was received into stock.
                </span>
            </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                        <tr>
                            <th class="py-3 px-3">Recv Date</th>
                            <th class="py-3 px-3">GRN #</th>
                            @if(!$selectedProduct)
                            <th class="py-3 px-3">Drug / Item</th>
                            @endif
                            <th class="py-3 px-3">Supplier</th>
                            <th class="py-3 px-3">Batch & Exp</th>
                            <th class="py-3 px-3 text-center">Qty Received</th>
                            <th class="py-3 px-3 text-right">Cost / Retail</th>
                            <th class="py-3 px-3 text-right">GRN Amount</th>
                            <th class="py-3 px-3 text-center no-print">View GRN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($grnItems as $gi)
                        @php
                            $grn = $gi->grn;
                            $uomLabel = $gi->product->base_uom ?? 'Unit';
                            $isPack = ($gi->purchase_uom === 'pack' && ($gi->units_per_pack ?? 1) > 1);
                            $grnModalPayload = [
                                'id' => $grn->id,
                                'grn_number' => $grn->grn_number,
                                'invoice_number' => $grn->invoice_number ?: 'N/A',
                                'invoice_date' => $grn->invoice_date ? $grn->invoice_date->format('d M Y') : '—',
                                'supplier_name' => $grn->supplier->name ?? 'Direct Purchase',
                                'supplier_phone' => $grn->supplier->phone ?? '',
                                'receiver_name' => $grn->receiver->name ?? 'Staff',
                                'status' => $grn->status,
                                'total_amount' => (float) $grn->total_amount,
                                'grn_url' => route('grn.show', $grn->id),
                                'highlight_product_id' => $gi->product_id,
                                'items' => $grn->items->map(fn($line) => [
                                    'product_id' => $line->product_id,
                                    'brand_name' => $line->product->brand_name ?? 'Item',
                                    'uom' => $line->product->base_uom ?? 'Unit',
                                    'batch_number' => $line->batch_number,
                                    'expiry_date' => $line->expiry_date ? $line->expiry_date->format('d/m/Y') : '—',
                                    'quantity_received' => (int) $line->quantity_received,
                                    'free_bonus_qty' => (int) $line->free_bonus_qty,
                                    'cost_price' => (float) $line->cost_price,
                                    'retail_price' => (float) $line->retail_price,
                                    'subtotal' => (float) $line->subtotal,
                                ])->values(),
                            ];
                        @endphp
                        <tr class="hover:bg-sky-50/40 transition-colors">
                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                {{ $grn->invoice_date ? $grn->invoice_date->format('d/m/Y') : $gi->created_at->format('d/m/Y') }}
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                                <button type="button"
                                        @click="openGrnModal({{ json_encode($grnModalPayload) }})"
                                        class="text-sky-700 hover:text-sky-900 hover:underline cursor-pointer">
                                    {{ $grn->grn_number }}
                                </button>
                                @if($grn->invoice_number)
                                <div class="text-[10px] text-slate-400 font-normal">Inv: {{ $grn->invoice_number }}</div>
                                @endif
                            </td>
                            @if(!$selectedProduct)
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ $gi->product->brand_name ?? 'Deleted Item' }}
                            </td>
                            @endif
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-slate-800 truncate max-w-[140px]">{{ $grn->supplier->name ?? 'Supplier' }}</div>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-[11px] whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $gi->batch_number }}</div>
                                <div class="text-[10px] text-slate-400">Exp: {{ $gi->expiry_date ? $gi->expiry_date->format('d/m/Y') : '—' }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono whitespace-nowrap">
                                @if($isPack && $gi->packs_received)
                                    <div class="font-bold text-indigo-700">{{ $gi->packs_received }} Packs ({{ number_format($gi->quantity_received) }} {{ $uomLabel }})</div>
                                @else
                                    <span class="px-2 py-0.5 rounded-lg bg-sky-50 text-sky-800 border border-sky-200 font-bold">
                                        {{ number_format($gi->quantity_received) }} {{ $uomLabel }}
                                    </span>
                                @endif
                                @if($gi->free_bonus_qty > 0)
                                    <div class="text-[10px] font-bold text-emerald-600 mt-0.5">+{{ number_format($gi->free_bonus_qty) }} Free Bonus</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-[11px] whitespace-nowrap">
                                <div class="text-slate-700">Cost: <strong>Rs. {{ number_format($gi->cost_price, 2) }}</strong></div>
                                <div class="text-emerald-700 text-[10px]">Sell: Rs. {{ number_format($gi->retail_price, 2) }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-black text-sky-800 whitespace-nowrap">
                                Rs. {{ number_format($gi->subtotal, 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap no-print">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            @click="openGrnModal({{ json_encode($grnModalPayload) }})"
                                            class="px-2.5 py-1 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-bold text-[11px] shadow-xs transition-colors cursor-pointer inline-flex items-center gap-1">
                                        <i class="fa-solid fa-eye text-[10px]"></i> View GRN
                                    </button>
                                    <a href="{{ route('grn.show', $grn->id) }}"
                                       title="Open Full GRN Sheet"
                                       class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-800 text-slate-600 hover:text-white transition-colors">
                                        <i class="fa-solid fa-up-right-from-square text-[11px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $selectedProduct ? 8 : 9 }}" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-truck-ramp-box text-2xl text-slate-300 mb-2 block"></i>
                                No GRN procurement records found for this filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($grnItems->count() > 0)
                    <tfoot class="bg-sky-50/70 border-t-2 border-sky-200 font-mono text-xs font-bold text-slate-900">
                        <tr>
                            <td colspan="{{ $selectedProduct ? 4 : 5 }}" class="py-3 px-3 text-right uppercase text-[10px] tracking-wider text-slate-600">
                                Total GRN Received:
                            </td>
                            <td class="py-3 px-3 text-center text-sky-900">
                                {{ number_format($grnSummary['total_qty_received']) }}
                                @if($grnSummary['total_bonus_received'] > 0)
                                    (+{{ number_format($grnSummary['total_bonus_received']) }} Free)
                                @endif
                            </td>
                            <td class="py-3 px-3"></td>
                            <td class="py-3 px-3 text-right text-sky-900 text-sm font-black">
                                Rs. {{ number_format($grnSummary['total_grn_amount'], 2) }}
                            </td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            @if($grnItems->hasPages())
            <div class="p-4 border-t border-slate-200 no-print">
                {{ $grnItems->withQueryString()->links() }}
            </div>
            @endif
        </div>

    </div>

    <!-- MODAL 1: Instant POS Sales Invoice Viewer Popup -->
    <div x-show="invoiceModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden"
             @click.outside="invoiceModalOpen = false">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-mono uppercase tracking-widest text-emerald-400">POS Sales Invoice Preview</div>
                        <h3 class="font-display font-bold text-base text-white" x-text="activeInvoice?.invoice_number"></h3>
                    </div>
                </div>
                <button type="button" @click="invoiceModalOpen = false" class="text-slate-400 hover:text-white text-xl leading-none cursor-pointer">&times;</button>
            </div>

            <div class="p-6 space-y-4 text-xs" x-show="activeInvoice">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Billed Date & Time</span>
                        <span class="font-mono font-bold text-slate-900" x-text="activeInvoice?.date"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Customer</span>
                        <span class="font-bold text-slate-900" x-text="activeInvoice?.customer_name"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Payment Mode</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800 inline-block mt-0.5"
                              x-text="activeInvoice?.payment_type"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Billed By Cashier</span>
                        <span class="font-semibold text-slate-800" x-text="activeInvoice?.cashier_name"></span>
                    </div>
                </div>

                <!-- Invoice Items Table -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-2.5 px-3.5">Medication / Item</th>
                                <th class="py-2.5 px-3.5 text-center">Qty Sold</th>
                                <th class="py-2.5 px-3.5 text-right">Unit Price</th>
                                <th class="py-2.5 px-3.5 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(line, idx) in (activeInvoice?.items || [])" :key="idx">
                                <tr :class="line.product_id == activeInvoice?.highlight_product_id ? 'bg-emerald-50 font-bold' : ''">
                                    <td class="py-2.5 px-3.5 text-slate-900">
                                        <span x-text="line.brand_name"></span>
                                        <span x-show="line.product_id == activeInvoice?.highlight_product_id"
                                              class="ml-1.5 px-1.5 py-0.2 rounded bg-emerald-600 text-white text-[9px] uppercase font-bold">
                                            Selected Drug
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-center font-mono" x-text="line.quantity + ' ' + line.uom"></td>
                                    <td class="py-2.5 px-3.5 text-right font-mono" x-text="'Rs. ' + Number(line.unit_price).toFixed(2)"></td>
                                    <td class="py-2.5 px-3.5 text-right font-mono font-bold text-slate-900" x-text="'Rs. ' + Number(line.subtotal).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Invoice Financial Totals -->
                <div class="bg-slate-900 text-white p-4 rounded-2xl flex flex-wrap items-center justify-between gap-4 font-mono">
                    <div class="space-y-0.5 text-xs">
                        <div class="text-slate-400">Subtotal: <span class="text-white" x-text="'Rs. ' + Number(activeInvoice?.subtotal || 0).toFixed(2)"></span></div>
                        <div class="text-slate-400">Bill Discount: <span class="text-amber-300" x-text="'- Rs. ' + Number(activeInvoice?.discount_amount || 0).toFixed(2)"></span></div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase text-slate-400 block">Invoice Grand Total</span>
                        <span class="text-xl font-black text-emerald-400" x-text="'Rs. ' + Number(activeInvoice?.grand_total || 0).toFixed(2)"></span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="invoiceModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">
                        Close
                    </button>
                    <a :href="activeInvoice?.receipt_url" target="_blank"
                       class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-print"></i>
                        <span>Open & Print Thermal Receipt</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Instant GRN Consignment Voucher Viewer Popup -->
    <div x-show="grnModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden"
             @click.outside="grnModalOpen = false">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-mono uppercase tracking-widest text-sky-400">GRN Consignment Voucher Preview</div>
                        <h3 class="font-display font-bold text-base text-white" x-text="activeGrn?.grn_number"></h3>
                    </div>
                </div>
                <button type="button" @click="grnModalOpen = false" class="text-slate-400 hover:text-white text-xl leading-none cursor-pointer">&times;</button>
            </div>

            <div class="p-6 space-y-4 text-xs" x-show="activeGrn">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Supplier / Distributor</span>
                        <span class="font-bold text-slate-900" x-text="activeGrn?.supplier_name"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Supplier Invoice #</span>
                        <span class="font-mono font-bold text-slate-900" x-text="activeGrn?.invoice_number"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Invoice Date</span>
                        <span class="font-mono font-bold text-slate-900" x-text="activeGrn?.invoice_date"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Received By</span>
                        <span class="font-semibold text-slate-800" x-text="activeGrn?.receiver_name"></span>
                    </div>
                </div>

                <!-- GRN Items Table -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden max-h-72 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="py-2.5 px-3">Drug / Item</th>
                                <th class="py-2.5 px-3">Batch & Expiry</th>
                                <th class="py-2.5 px-3 text-center">Qty Received</th>
                                <th class="py-2.5 px-3 text-right">Unit Cost</th>
                                <th class="py-2.5 px-3 text-right">Retail Price</th>
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(line, idx) in (activeGrn?.items || [])" :key="idx">
                                <tr :class="line.product_id == activeGrn?.highlight_product_id ? 'bg-sky-50 font-bold' : ''">
                                    <td class="py-2.5 px-3 text-slate-900">
                                        <span x-text="line.brand_name"></span>
                                        <span x-show="line.product_id == activeGrn?.highlight_product_id"
                                              class="ml-1.5 px-1.5 py-0.2 rounded bg-sky-600 text-white text-[9px] uppercase font-bold">
                                            Selected Drug
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-[11px]">
                                        <span x-text="line.batch_number"></span>
                                        <span class="text-slate-400 block text-[10px]" x-text="'Exp: ' + line.expiry_date"></span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono">
                                        <span x-text="line.quantity_received + ' ' + line.uom"></span>
                                        <span x-show="line.free_bonus_qty > 0" class="text-emerald-600 block text-[10px]" x-text="'+' + line.free_bonus_qty + ' Free'"></span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono" x-text="'Rs. ' + Number(line.cost_price).toFixed(2)"></td>
                                    <td class="py-2.5 px-3 text-right font-mono text-emerald-700" x-text="'Rs. ' + Number(line.retail_price).toFixed(2)"></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900" x-text="'Rs. ' + Number(line.subtotal).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- GRN Financial Total -->
                <div class="bg-slate-900 text-white p-4 rounded-2xl flex items-center justify-between font-mono">
                    <span class="text-xs text-slate-400">GRN Status: <strong class="text-emerald-400 uppercase" x-text="activeGrn?.status"></strong></span>
                    <div class="text-right">
                        <span class="text-[10px] uppercase text-slate-400 block">Total GRN Consignment Value</span>
                        <span class="text-xl font-black text-sky-400" x-text="'Rs. ' + Number(activeGrn?.total_amount || 0).toFixed(2)"></span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="grnModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer">
                        Close
                    </button>
                    <a :href="activeGrn?.grn_url"
                       class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>Open Full GRN Consignment Sheet</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function itemVsTransactionApp() {
    const catalog = @json($catalogJson);
    const initialSelected = @json($selectedProductJson);

    return {
        catalog: catalog,
        selectedId: initialSelected ? initialSelected.id : '',
        searchQuery: initialSelected
            ? `${initialSelected.brand_name}${initialSelected.dosage_strength ? ' (' + initialSelected.dosage_strength + ')' : ''} [${initialSelected.barcode_sku}]`
            : '',
        dropdownOpen: false,
        startDate: @json($startDate),
        endDate: @json($endDate),
        preset: @json($preset),
        grnScope: @json($grnScope),
        layoutMode: 'split',
        invoiceModalOpen: false,
        activeInvoice: null,
        grnModalOpen: false,
        activeGrn: null,

        get filteredProducts() {
            const q = (this.searchQuery || '').toLowerCase().trim();
            if (!q || (initialSelected && this.searchQuery.includes(initialSelected.barcode_sku))) {
                return this.catalog.slice(0, 60);
            }
            return this.catalog.filter(p =>
                (p.brand_name && p.brand_name.toLowerCase().includes(q)) ||
                (p.generic_name && p.generic_name.toLowerCase().includes(q)) ||
                (p.barcode_sku && p.barcode_sku.toLowerCase().includes(q))
            ).slice(0, 50);
        },

        chooseProduct(prod) {
            this.selectedId = prod.id;
            this.searchQuery = `${prod.brand_name}${prod.dosage_strength ? ' (' + prod.dosage_strength + ')' : ''} [${prod.barcode_sku}]`;
            this.dropdownOpen = false;
            this.$nextTick(() => {
                document.getElementById('itemReportFilterForm').submit();
            });
        },

        clearDrugSearch() {
            this.searchQuery = '';
            this.dropdownOpen = true;
        },

        selectAllDrugs() {
            this.selectedId = '';
            this.searchQuery = '';
            this.dropdownOpen = false;
            this.$nextTick(() => {
                document.getElementById('itemReportFilterForm').submit();
            });
        },

        applyPreset(presetName) {
            this.preset = presetName;
            this.$nextTick(() => {
                document.getElementById('itemReportFilterForm').submit();
            });
        },

        openInvoiceModal(payload) {
            this.activeInvoice = payload;
            this.invoiceModalOpen = true;
        },

        openGrnModal(payload) {
            this.activeGrn = payload;
            this.grnModalOpen = true;
        }
    };
}
</script>
@endpush
@endsection
