@extends('layouts.app')

@section('title', 'Item Stock Movement & Date-Range Comparison Report')

@push('styles')
<style>
    @media print {
        @page { size: landscape; margin: 10mm; }
        body { background: white !important; }
    }
</style>
@endpush

@section('content')
@php
    $catalogJson = $allCatalogProducts->map(fn($p) => [
        'id' => $p->id,
        'barcode_sku' => $p->barcode_sku,
        'brand_name' => $p->brand_name,
        'generic_name' => $p->generic_name ?? '',
        'category_name' => $p->category?->name ?? 'General',
        'base_uom' => $p->base_uom ?? 'Unit',
    ])->values();

    $startShort = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
    $endShort = \Carbon\Carbon::parse($endDate)->format('Y-m-d');
    $startLabel = \Carbon\Carbon::parse($startDate)->format('d M Y');
    $endLabel = \Carbon\Carbon::parse($endDate)->format('d M Y');
@endphp

<div class="max-w-[1500px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
     x-data="stockMovementReportApp()">

    <!-- Top Breadcrumb & Export Bar (No Print) -->
    <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.index') }}" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-600 hover:text-slate-900 shadow-2xs flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i> Reports Hub
            </a>
            <span class="text-xs font-mono text-slate-400">Report #12 · Inventory Comparison & Audit</span>
        </div>

        <!-- Export Buttons (Excel .xlsx, CSV, PDF, Print) -->
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="exportToExcel()"
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-2 transition-all cursor-pointer">
                <i class="fa-solid fa-file-excel"></i>
                <span>Export Excel (.xlsx)</span>
            </button>

            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="px-3.5 py-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 text-xs font-bold flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-file-csv"></i>
                <span>CSV</span>
            </a>

            <button type="button"
                    @click="exportToPdf()"
                    :disabled="exportingPdf"
                    class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-xs font-bold shadow-sm flex items-center gap-2 transition-all cursor-pointer">
                <i class="fa-solid fa-file-pdf"></i>
                <span x-text="exportingPdf ? 'Generating PDF...' : 'Export PDF'"></span>
            </button>

            <button type="button"
                    onclick="window.print()"
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer">
                <i class="fa-solid fa-print"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <!-- Filter & Custom Item Row Selector Panel (No Print) -->
    <div class="no-print bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-4">
        <form method="GET" action="{{ route('reports.item-stock-movement') }}" x-ref="filterForm" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <!-- Start Date (Initial Stock Date) -->
                <div class="lg:col-span-2">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                        From Date (Initial Stock)
                    </label>
                    <input type="date" name="start_date" value="{{ $startDate }}" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <!-- End Date (Closing Stock Date) -->
                <div class="lg:col-span-2">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                        To Date (Closing Stock)
                    </label>
                    <input type="date" name="end_date" value="{{ $endDate }}" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <!-- Therapeutic Category Filter -->
                <div class="lg:col-span-3">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                        Item Category
                    </label>
                    <select name="category_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-semibold focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">-- All Categories --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Add Specific Item to Report Row Picker -->
                <div class="lg:col-span-3 relative" @click.outside="itemDropdownOpen = false">
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1 text-[11px]">
                        + Add Specific Item(s) to Report
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-plus-circle text-emerald-600 absolute left-3 top-2.5"></i>
                        <input type="text"
                               x-model="itemPickerQuery"
                               @focus="itemDropdownOpen = true"
                               @input="itemDropdownOpen = true"
                               placeholder="Search & click item to add row..."
                               class="w-full pl-8 pr-3 py-2 bg-emerald-50/50 border border-emerald-300 rounded-xl text-xs font-medium focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <!-- Autocomplete Dropdown -->
                    <div x-show="itemDropdownOpen && filteredCatalog.length > 0" x-cloak
                         class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-2xl shadow-2xl max-h-60 overflow-y-auto z-40 divide-y divide-slate-100">
                        <template x-for="prod in filteredCatalog" :key="prod.id">
                            <button type="button"
                                    @click="addProductToSelection(prod)"
                                    class="w-full text-left px-3 py-2 hover:bg-emerald-50 flex items-center justify-between transition-colors">
                                <div>
                                    <div class="font-bold text-slate-800 text-xs" x-text="prod.brand_name"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="prod.barcode_sku + ' · ' + prod.category_name"></div>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">+ Add Row</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Filter / Generate Button -->
                <div class="lg:col-span-2 flex items-center gap-1.5">
                    <button type="submit"
                            class="flex-1 py-2 px-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                        <i class="fa-solid fa-filter"></i>
                        <span>Apply Filter</span>
                    </button>
                    <a href="{{ route('reports.item-stock-movement') }}"
                       title="Reset Filters"
                       class="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-semibold">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </div>

            <!-- Selected / Added Items Pill Bar -->
            <template x-if="selectedIds.length > 0">
                <div class="pt-2 border-t border-slate-100 flex items-center flex-wrap gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1">
                        <i class="fa-solid fa-thumbtack"></i> Pinned Report Items (<span x-text="selectedIds.length"></span>):
                    </span>
                    <template x-for="pid in selectedIds" :key="pid">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                            <input type="hidden" name="product_ids[]" :value="pid">
                            <span x-text="getProductName(pid)"></span>
                            <button type="button" @click="removeProductFromSelection(pid)" class="text-emerald-600 hover:text-rose-600 font-bold">&times;</button>
                        </span>
                    </template>
                    <button type="button"
                            @click="clearSelectedProducts()"
                            class="text-[11px] font-semibold text-rose-600 hover:underline ml-2">
                        Clear All (Show All Category Items)
                    </button>
                </div>
            </template>
        </form>
    </div>

    <!-- PRINTABLE / PDF REPORT CONTAINER -->
    <div id="printableReportArea" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-6">

        <!-- Official Report Banner Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b-2 border-slate-900">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-md bg-emerald-600 text-white text-[10px] font-bold uppercase tracking-widest font-mono">
                        PharmaCare POS Pro · Inventory Audit
                    </span>
                    <span class="text-xs text-slate-400 font-mono">Generated: {{ now()->format('d M Y, h:i A') }}</span>
                </div>
                <h1 class="text-2xl font-extrabold font-display text-slate-900 tracking-tight mt-1">
                    Item Stock Movement & Date-Range Comparison Report
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Comparing Opening / Initial Stock on <strong>{{ $startLabel }} ({{ $startShort }})</strong> vs. Closing Stock on <strong>{{ $endLabel }} ({{ $endShort }})</strong> with Net Stock Difference.
                </p>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-right shrink-0">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Audit Period</div>
                <div class="text-sm font-black font-mono text-slate-900">{{ $startShort }} &rarr; {{ $endShort }}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">
                    Category: <strong>{{ $categoryId ? ($categories->firstWhere('id', $categoryId)?->name ?? 'Selected') : 'All Categories' }}</strong>
                    · Items: <strong>{{ $summary['items_count'] }}</strong>
                </div>
            </div>
        </div>

        <!-- Executive Summary Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    {{ \Carbon\Carbon::parse($startDate)->format('m-d') }} Initial Stock
                </div>
                <div class="text-lg font-black font-mono text-slate-900 mt-0.5">
                    {{ number_format($summary['total_start_stock']) }}
                </div>
                <div class="text-[10px] text-slate-500 font-mono">Cost: Rs. {{ number_format($summary['total_start_value'], 2) }}</div>
            </div>

            <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200">
                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">
                    + GRN Received
                </div>
                <div class="text-lg font-black font-mono text-emerald-700 mt-0.5">
                    +{{ number_format($summary['total_grn_in']) }}
                </div>
                <div class="text-[10px] text-emerald-600">Added in period</div>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200">
                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-800">
                    - POS Sold Out
                </div>
                <div class="text-lg font-black font-mono text-amber-800 mt-0.5">
                    -{{ number_format($summary['total_sold_out']) }}
                </div>
                <div class="text-[10px] text-amber-700">Dispensed in period</div>
            </div>

            <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-200">
                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-700">
                    +/- Returns & Adj.
                </div>
                <div class="text-lg font-black font-mono text-indigo-700 mt-0.5">
                    {{ $summary['total_returns_adj'] > 0 ? '+' : '' }}{{ number_format($summary['total_returns_adj']) }}
                </div>
                <div class="text-[10px] text-indigo-600">Net returns & audit</div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-900 text-white">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    {{ \Carbon\Carbon::parse($endDate)->format('m-d') }} Closing Stock
                </div>
                <div class="text-lg font-black font-mono text-emerald-400 mt-0.5">
                    {{ number_format($summary['total_end_stock']) }}
                </div>
                <div class="text-[10px] text-slate-300 font-mono">Cost: Rs. {{ number_format($summary['total_end_value'], 2) }}</div>
            </div>

            <div class="p-3.5 rounded-2xl border {{ $summary['total_difference'] >= 0 ? 'bg-emerald-50 border-emerald-300' : 'bg-rose-50 border-rose-300' }}">
                <div class="text-[10px] font-bold uppercase tracking-wider {{ $summary['total_difference'] >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">
                    Stock Difference
                </div>
                <div class="text-lg font-black font-mono mt-0.5 {{ $summary['total_difference'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                    {{ $summary['total_difference'] > 0 ? '+' : '' }}{{ number_format($summary['total_difference']) }}
                </div>
                <div class="text-[10px] font-semibold {{ $summary['total_difference'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ \Carbon\Carbon::parse($endDate)->format('m-d') }} vs {{ \Carbon\Carbon::parse($startDate)->format('m-d') }}
                </div>
            </div>
        </div>

        <!-- Main Comparison Table -->
        <div class="overflow-x-auto border border-slate-200 rounded-2xl">
            <table id="stockMovementTable" class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-3">#</th>
                        <th class="py-3 px-3">SKU</th>
                        <th class="py-3 px-3">Item / Medication</th>
                        <th class="py-3 px-3">Category</th>
                        <th class="py-3 px-3 text-center">UOM</th>
                        <th class="py-3 px-3 text-right bg-slate-800">
                            {{ \Carbon\Carbon::parse($startDate)->format('Y-m-d') }}<br>
                            <span class="text-emerald-300">Initial Stock</span>
                        </th>
                        <th class="py-3 px-3 text-right">GRN In (+)</th>
                        <th class="py-3 px-3 text-right">Sold Out (-)</th>
                        <th class="py-3 px-3 text-right">Returns / Adj</th>
                        <th class="py-3 px-3 text-right bg-slate-800">
                            {{ \Carbon\Carbon::parse($endDate)->format('Y-m-d') }}<br>
                            <span class="text-amber-300">Closing Stock</span>
                        </th>
                        <th class="py-3 px-3 text-center bg-slate-800/90">
                            Stock Difference<br>
                            <span class="text-slate-300">({{ \Carbon\Carbon::parse($endDate)->format('m-d') }} - {{ \Carbon\Carbon::parse($startDate)->format('m-d') }})</span>
                        </th>
                        <th class="py-3 px-3 text-right">Unit Cost</th>
                        <th class="py-3 px-3 text-right">Retail Price</th>
                        <th class="py-3 px-3 text-right">Closing Value (Rs.)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 font-medium">
                    @forelse($reportRows as $index => $row)
                    <tr class="hover:bg-slate-50/90 transition-colors">
                        <td class="py-2.5 px-3 font-mono text-slate-400">{{ $index + 1 }}</td>
                        <td class="py-2.5 px-3 font-mono text-[11px] font-semibold text-slate-600">{{ $row['barcode_sku'] }}</td>
                        <td class="py-2.5 px-3">
                            <div class="font-bold text-slate-900">{{ $row['brand_name'] }}</div>
                            @if($row['generic_name'] && $row['generic_name'] !== '—')
                            <div class="text-[10px] text-slate-400 italic">{{ $row['generic_name'] }}</div>
                            @endif
                        </td>
                        <td class="py-2.5 px-3">
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-semibold">
                                {{ $row['category_name'] }}
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-center font-mono text-[11px]">
                            {{ $row['base_uom'] }}
                            @if($row['units_per_pack'] > 1)
                            <span class="block text-[9px] text-indigo-600 font-bold">1 Pk = {{ $row['units_per_pack'] }}</span>
                            @endif
                        </td>

                        <!-- Initial Stock on Start Date -->
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 bg-slate-50/80">
                            {{ number_format($row['start_stock']) }}
                            @if($row['units_per_pack'] > 1)
                            <span class="block text-[9px] text-slate-400 font-normal">
                                ({{ round($row['start_stock'] / $row['units_per_pack'], 1) }} Pk)
                            </span>
                            @endif
                        </td>

                        <!-- GRN In -->
                        <td class="py-2.5 px-3 text-right font-mono {{ $row['grn_in'] > 0 ? 'text-emerald-700 font-bold' : 'text-slate-400' }}">
                            {{ $row['grn_in'] > 0 ? '+' . number_format($row['grn_in']) : '0' }}
                        </td>

                        <!-- Sold Out -->
                        <td class="py-2.5 px-3 text-right font-mono {{ $row['sold_out'] > 0 ? 'text-amber-700 font-bold' : 'text-slate-400' }}">
                            {{ $row['sold_out'] > 0 ? '-' . number_format($row['sold_out']) : '0' }}
                        </td>

                        <!-- Returns & Adjustments -->
                        <td class="py-2.5 px-3 text-right font-mono {{ $row['returns_adj_net'] != 0 ? 'text-indigo-700 font-bold' : 'text-slate-400' }}">
                            {{ $row['returns_adj_net'] > 0 ? '+' . number_format($row['returns_adj_net']) : number_format($row['returns_adj_net']) }}
                        </td>

                        <!-- Closing Stock on End Date -->
                        <td class="py-2.5 px-3 text-right font-mono font-black text-slate-900 bg-slate-50/80">
                            {{ number_format($row['end_stock']) }}
                            @if($row['units_per_pack'] > 1)
                            <span class="block text-[9px] text-slate-400 font-normal">
                                ({{ round($row['end_stock'] / $row['units_per_pack'], 1) }} Pk)
                            </span>
                            @endif
                        </td>

                        <!-- Stock Difference -->
                        <td class="py-2.5 px-3 text-center font-mono">
                            @if($row['stock_difference'] > 0)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold text-[11px]">
                                    +{{ number_format($row['stock_difference']) }}
                                </span>
                            @elseif($row['stock_difference'] < 0)
                                <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-300 font-bold text-[11px]">
                                    {{ number_format($row['stock_difference']) }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-semibold text-[11px]">
                                    0 (No Change)
                                </span>
                            @endif
                        </td>

                        <td class="py-2.5 px-3 text-right font-mono text-slate-600">
                            {{ number_format($row['unit_cost'], 2) }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-semibold text-emerald-700">
                            {{ number_format($row['unit_retail'], 2) }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                            {{ number_format($row['end_value_cost'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="py-8 text-center text-slate-400">
                            No items matched your filter criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($reportRows->isNotEmpty())
                <tfoot class="bg-slate-900 text-white font-mono text-xs font-bold">
                    <tr>
                        <td colspan="5" class="py-3 px-3 text-right uppercase tracking-wider text-slate-300">Total Summary:</td>
                        <td class="py-3 px-3 text-right text-emerald-300">{{ number_format($summary['total_start_stock']) }}</td>
                        <td class="py-3 px-3 text-right text-emerald-400">+{{ number_format($summary['total_grn_in']) }}</td>
                        <td class="py-3 px-3 text-right text-amber-300">-{{ number_format($summary['total_sold_out']) }}</td>
                        <td class="py-3 px-3 text-right text-indigo-300">{{ $summary['total_returns_adj'] > 0 ? '+' : '' }}{{ number_format($summary['total_returns_adj']) }}</td>
                        <td class="py-3 px-3 text-right text-amber-300">{{ number_format($summary['total_end_stock']) }}</td>
                        <td class="py-3 px-3 text-center {{ $summary['total_difference'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $summary['total_difference'] > 0 ? '+' : '' }}{{ number_format($summary['total_difference']) }}
                        </td>
                        <td colspan="2" class="py-3 px-3 text-right text-slate-400">Total Cost Asset:</td>
                        <td class="py-3 px-3 text-right text-emerald-400">Rs. {{ number_format($summary['total_end_value'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

    </div>

</div>

@push('scripts')
<!-- SheetJS (xlsx) & html2pdf.js CDNs for Instant Client-Side Excel (.xlsx) & PDF Exports -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function stockMovementReportApp() {
    return {
        catalog: @json($catalogJson),
        selectedIds: @json(array_values(array_map('intval', $selectedProductIds))),
        reportData: @json($reportRows->values()),
        startDate: '{{ $startShort }}',
        endDate: '{{ $endShort }}',
        itemPickerQuery: '',
        itemDropdownOpen: false,
        exportingPdf: false,

        get filteredCatalog() {
            const q = this.itemPickerQuery.trim().toLowerCase();
            return this.catalog.filter(p => {
                if (this.selectedIds.includes(p.id)) return false;
                if (!q) return true;
                const words = q.split(/\s+/);
                const hay = (p.brand_name + ' ' + p.generic_name + ' ' + p.barcode_sku + ' ' + p.category_name).toLowerCase();
                return words.every(w => hay.includes(w));
            }).slice(0, 15);
        },

        getProductName(id) {
            const p = this.catalog.find(x => x.id == id);
            return p ? p.brand_name : ('Item #' + id);
        },

        addProductToSelection(prod) {
            if (!this.selectedIds.includes(prod.id)) {
                this.selectedIds.push(prod.id);
            }
            this.itemPickerQuery = '';
            this.itemDropdownOpen = false;
            this.$nextTick(() => this.$refs.filterForm.submit());
        },

        removeProductFromSelection(id) {
            this.selectedIds = this.selectedIds.filter(x => x != id);
            this.$nextTick(() => this.$refs.filterForm.submit());
        },

        clearSelectedProducts() {
            this.selectedIds = [];
            this.$nextTick(() => this.$refs.filterForm.submit());
        },

        exportToExcel() {
            if (typeof XLSX === 'undefined') {
                window.location.href = `{!! request()->fullUrlWithQuery(['export' => 'csv']) !!}`;
                return;
            }

            const rows = [
                ['PHARMACARE POS PRO - ITEM STOCK MOVEMENT & DATE-RANGE COMPARISON REPORT'],
                [`Period: ${this.startDate} to ${this.endDate}`, `Generated: ${new Date().toLocaleString()}`],
                [],
                [
                    '#',
                    'SKU / Barcode',
                    'Item / Medication Name',
                    'Generic Composition',
                    'Category',
                    'Base UOM',
                    'Units / Pack',
                    `Initial Stock (${this.startDate})`,
                    'GRN Received (+)',
                    'POS Sold (-)',
                    'Returns & Adjustments (+/-)',
                    `Closing Stock (${this.endDate})`,
                    `Stock Difference (${this.endDate} - ${this.startDate})`,
                    'Unit Cost (Rs.)',
                    'Retail Price (Rs.)',
                    'Closing Stock Cost Value (Rs.)'
                ]
            ];

            this.reportData.forEach((r, idx) => {
                rows.push([
                    idx + 1,
                    r.barcode_sku,
                    r.brand_name,
                    r.generic_name,
                    r.category_name,
                    r.base_uom,
                    r.units_per_pack,
                    r.start_stock,
                    r.grn_in,
                    r.sold_out,
                    r.returns_adj_net,
                    r.end_stock,
                    r.stock_difference,
                    parseFloat(r.unit_cost),
                    parseFloat(r.unit_retail),
                    parseFloat(r.end_value_cost)
                ]);
            });

            rows.push([
                '',
                '',
                'TOTAL SUMMARY',
                '',
                '',
                '',
                '',
                {{ $summary['total_start_stock'] }},
                {{ $summary['total_grn_in'] }},
                {{ $summary['total_sold_out'] }},
                {{ $summary['total_returns_adj'] }},
                {{ $summary['total_end_stock'] }},
                {{ $summary['total_difference'] }},
                '',
                'Total Value:',
                {{ round($summary['total_end_value'], 2) }}
            ]);

            const ws = XLSX.utils.aoa_to_sheet(rows);
            ws['!cols'] = [
                { wch: 5 }, { wch: 15 }, { wch: 28 }, { wch: 22 }, { wch: 18 },
                { wch: 10 }, { wch: 12 }, { wch: 20 }, { wch: 16 }, { wch: 14 },
                { wch: 22 }, { wch: 20 }, { wch: 24 }, { wch: 14 }, { wch: 14 }, { wch: 22 }
            ];

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Stock Movement');
            XLSX.writeFile(wb, `Item_Stock_Movement_${this.startDate}_to_${this.endDate}.xlsx`);
        },

        async exportToPdf() {
            if (typeof html2pdf === 'undefined') {
                window.print();
                return;
            }
            this.exportingPdf = true;
            try {
                const element = document.getElementById('printableReportArea');
                const opt = {
                    margin: [8, 8, 8, 8],
                    filename: `Item_Stock_Movement_${this.startDate}_to_${this.endDate}.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                };
                await html2pdf().set(opt).from(element).save();
            } catch (e) {
                console.error(e);
                window.print();
            } finally {
                this.exportingPdf = false;
            }
        }
    };
}
</script>
@endpush
@endsection
