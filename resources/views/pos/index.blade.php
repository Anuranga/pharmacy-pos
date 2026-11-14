@extends('layouts.app')

@section('title', 'Fast Counter POS Terminal')

@section('content')
<div class="h-[calc(100vh-4rem)] flex flex-col p-2 gap-1.5 overflow-hidden" 
     x-data="posTerminal()"
     x-init="init()"
     @keydown.window.f2.prevent="focusSearch()"
     @keydown.window.f8.prevent="openPaymentModal()"
     @keydown.window.escape.prevent="handleEscape()">

    <!-- Top Shift & Status Bar (Compact) -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 px-3 py-1 flex items-center justify-between shrink-0">
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[11px] font-bold text-slate-700 tracking-wide uppercase">Register Online</span>
                <span class="text-xs text-slate-300">|</span>
                <span class="text-[11px] text-slate-500 font-mono">Invoice: <strong class="text-slate-900" x-text="nextInvoicePreview">INV-PENDING</strong></span>
            </div>

            <!-- Quick Hotkey Pills -->
            <div class="hidden lg:flex items-center space-x-1.5 text-[10px] font-medium text-slate-500">
                <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200"><strong class="font-mono text-emerald-600">F2</strong> Search</span>
                <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200"><strong class="font-mono text-emerald-600">F8</strong> Pay / Checkout</span>
                <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200"><strong class="font-mono text-rose-600">Esc</strong> Clear / Close</span>
            </div>
        </div>

        <!-- Shift Metrics & Billing Width Toggle -->
        <div class="flex items-center space-x-3 text-[11px] font-semibold">
            <div class="text-slate-600">
                Today Bills: <span class="font-bold text-slate-900">{{ $stats['today_sales_count'] }}</span>
            </div>
            <div class="text-slate-600">
                Today Revenue: <span class="font-bold text-emerald-600">Rs. {{ number_format($stats['today_revenue'], 2) }}</span>
            </div>
            <div class="text-slate-600 hidden sm:block">
                Cash Drawer: <span class="font-bold text-teal-600">Rs. {{ number_format($stats['today_cash'], 2) }}</span>
            </div>
            <button type="button"
                    @click="wideBilling = !wideBilling"
                    :class="wideBilling ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'"
                    class="hidden lg:inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border text-[10px] font-bold transition-colors"
                    title="Toggle Extra-Wide Billing View">
                <i class="fa-solid fa-up-right-and-down-left-from-center text-[9px]"></i>
                <span x-text="wideBilling ? 'Standard Split' : 'Expand Bill'"></span>
            </button>
        </div>
    </div>

    <!-- Main POS Grid (Left: Catalog/Search, Right: Live Cart & Bill) -->
    <div class="flex-1 grid grid-cols-12 gap-2 min-h-0">

        <!-- LEFT PANEL: Search, Filter & Medicine Grid -->
        <div :class="wideBilling ? 'lg:col-span-5' : 'lg:col-span-6'"
             class="col-span-12 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden transition-all">
            
            <!-- Barcode & Search Controls -->
            <div class="p-2.5 border-b border-slate-200 bg-slate-50/50 space-y-1.5">
                <div class="grid grid-cols-12 gap-1.5">
                    <!-- Barcode Scanner Input (Auto-Focusable) -->
                    <div class="col-span-5 relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-barcode text-sm text-emerald-600"></i>
                        </div>
                        <input type="text" 
                               x-ref="barcodeInput"
                               x-model="barcodeQuery"
                               @keydown.enter.prevent="scanBarcode()"
                               placeholder="Scan Barcode (Enter)..." 
                               class="w-full pl-8 pr-2.5 py-1.5 bg-white text-xs font-mono font-medium rounded-xl border border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400 shadow-xs">
                    </div>

                    <!-- Keyword Search (Brand / Generic Salt) -->
                    <div class="col-span-5 relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </div>
                        <input type="text" 
                               x-ref="searchInput"
                               x-model="searchQuery"
                               @input.debounce.150ms="performSearch()"
                               placeholder="Search Brand or Generic [F2]..." 
                               class="w-full pl-7 pr-7 py-1.5 bg-white text-xs font-medium rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400 shadow-xs">
                        <button x-show="searchQuery" @click="searchQuery = ''; performSearch()" class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 text-xs">
                            <i class="fa-solid fa-times-circle"></i>
                        </button>
                    </div>

                    <!-- Quick Add Product Button -->
                    <div class="col-span-2">
                        <button type="button" 
                                @click="openQuickCreateModal = true"
                                class="w-full h-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[11px] font-semibold flex items-center justify-center gap-1 shadow-xs transition-colors">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>New Item</span>
                        </button>
                    </div>
                </div>

                <!-- Therapeutic Category Pill Filters -->
                <div class="flex items-center space-x-1.5 overflow-x-auto pb-0.5 text-xs no-scrollbar">
                    <button type="button" 
                            @click="selectedCategory = null; performSearch()"
                            :class="selectedCategory === null ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                            class="px-2 py-0.5 rounded-lg text-[11px] whitespace-nowrap transition-colors">
                        All Categories
                    </button>
                    @foreach($categories as $cat)
                    <button type="button" 
                            @click="selectedCategory = {{ $cat->id }}; performSearch()"
                            :class="selectedCategory === {{ $cat->id }} ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                            class="px-2 py-0.5 rounded-lg text-[11px] whitespace-nowrap transition-colors">
                        {{ $cat->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Cards Stream (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-2.5 relative">
                
                <!-- Loading Overlay Spinner -->
                <div x-show="loadingProducts" x-cloak 
                     class="absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center z-20">
                    <div class="flex items-center space-x-2 text-emerald-600 text-sm font-semibold">
                        <i class="fa-solid fa-circle-notch fa-spin text-lg"></i>
                        <span>Loading Medicines...</span>
                    </div>
                </div>

                <!-- No Results -->
                <template x-if="!loadingProducts && products.length === 0">
                    <div class="h-64 flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-solid fa-prescription-bottle-medical text-4xl mb-2 text-slate-300"></i>
                        <p class="text-sm font-medium">No medication found matching query</p>
                        <p class="text-xs text-slate-400 mt-1">Check spelling or use the <strong class="text-emerald-600">+ New Item</strong> button to register it.</p>
                    </div>
                </template>

                <!-- Grid of Medicines -->
                <div :class="wideBilling ? 'grid-cols-2' : 'grid-cols-2 xl:grid-cols-3'" class="grid gap-2">
                    <template x-for="product in products" :key="product.id">
                        <div @click="addToCart(product)"
                             class="group relative bg-white hover:bg-emerald-50/40 rounded-xl border border-slate-200 hover:border-emerald-400 p-2 flex flex-col justify-between cursor-pointer transition-all shadow-xs hover:shadow-md select-none">
                            
                            <!-- Badges Header -->
                            <div class="flex items-start justify-between gap-1 mb-1">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="text-[9px] font-mono font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200" x-text="product.item_type"></span>
                                    <template x-if="product.units_per_pack > 1">
                                        <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200"
                                              x-text="'1 Pack = ' + product.units_per_pack + ' ' + (product.base_uom || 'Tab')"></span>
                                    </template>
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <template x-if="product.price_options && product.price_options.length > 1">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300"
                                              x-text="product.price_options.length + ' Batch Prices'"></span>
                                    </template>
                                    <template x-if="product.requires_prescription">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 border border-rose-200 uppercase tracking-wider">Rx</span>
                                    </template>
                                    <template x-if="product.is_controlled">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 border border-purple-200 uppercase tracking-wider">Ctrl</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Drug Brand Name & Composition -->
                            <div class="mb-1.5">
                                <h4 class="font-bold text-slate-800 text-xs group-hover:text-emerald-700 line-clamp-1" x-text="product.brand_name"></h4>
                                <p class="text-[11px] text-slate-500 font-medium italic line-clamp-1" x-text="product.generic_name"></p>
                                <p class="text-[10px] text-slate-400 font-mono" x-text="product.dosage_strength || product.barcode_sku"></p>
                            </div>

                            <!-- Stock & FEFO Batch Badge -->
                            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div>
                                    <div class="flex items-baseline gap-1 flex-wrap">
                                        <span class="font-display font-bold text-sm text-emerald-700" x-text="'Rs. ' + parseFloat(product.retail_price).toFixed(2)"></span>
                                        <span class="text-[9px] text-slate-400 font-mono" x-text="'/' + (product.base_uom || 'Unit')"></span>
                                    </div>
                                    <template x-if="product.price_options && product.price_options.length > 1">
                                        <div class="text-[10px] font-mono font-semibold text-amber-700"
                                             x-text="'Batches: ' + product.price_options.map(o => 'Rs.' + parseFloat(o.price).toFixed(0)).join(' / ')"></div>
                                    </template>
                                    <template x-if="product.units_per_pack > 1">
                                        <div class="text-[10px] font-mono font-semibold text-indigo-600"
                                             x-text="'Pack (' + product.units_per_pack + '): Rs. ' + (parseFloat(product.retail_price) * product.units_per_pack).toFixed(2)"></div>
                                    </template>
                                </div>

                                <div class="text-right">
                                    <template x-if="product.total_stock > 0">
                                        <div class="flex items-center justify-end gap-1">
                                            <span class="text-[10px] font-semibold text-slate-600" x-text="product.total_stock + ' ' + (product.base_uom || 'units')"></span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" title="In Stock"></span>
                                        </div>
                                    </template>
                                    <template x-if="product.total_stock === 0">
                                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">0 STOCK</span>
                                    </template>
                                    <template x-if="product.total_stock < 0">
                                        <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200"
                                              x-text="product.total_stock + ' ' + (product.base_uom || 'units')"></span>
                                    </template>

                                    <!-- Earliest Expiry FEFO Badge -->
                                    <template x-if="product.earliest_expiry">
                                        <span class="text-[9px] text-slate-400 block font-mono">Exp: <span x-text="product.earliest_expiry"></span></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Quick Unit vs Pack Add Buttons (when units_per_pack > 1) -->
                            <template x-if="product.units_per_pack > 1">
                                <div class="mt-1.5 pt-1.5 border-t border-slate-100 grid grid-cols-2 gap-1" @click.stop>
                                    <button type="button"
                                            @click="addToCart(product, 'unit')"
                                            class="py-1 px-1.5 rounded-lg bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 text-[10px] font-bold transition-colors flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-pills text-[9px]"></i>
                                        <span x-text="'+1 ' + (product.base_uom || 'Tablet')"></span>
                                    </button>
                                    <button type="button"
                                            @click="addToCart(product, 'pack')"
                                            class="py-1 px-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 border border-indigo-200 text-[10px] font-bold transition-colors flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-box-open text-[9px]"></i>
                                        <span x-text="'+1 Pack (' + product.units_per_pack + ')'"></span>
                                    </button>
                                </div>
                            </template>

                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- RIGHT PANEL: Live Checkout Cart & Billing Register (Expanded Space) -->
        <div :class="wideBilling ? 'lg:col-span-7' : 'lg:col-span-6'"
             class="col-span-12 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden transition-all"
             x-data="{ rxOpen: false }">
            
            <!-- Compact Single-Row Customer & Prescription Header -->
            <div class="px-2.5 py-1.5 border-b border-slate-200 bg-slate-50/70 space-y-1.5 shrink-0">
                <div class="flex items-center gap-1.5">
                    <!-- Customer Selection -->
                    <div class="flex-1 relative">
                        <select x-model="selectedCustomerId" 
                                @change="onCustomerChange()"
                                class="w-full pl-2.5 pr-7 py-1 bg-white text-xs font-semibold rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="">-- Walk-in Customer (Cash / Card / QR) --</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }} ({{ $c->phone }}) - Debt: Rs. {{ number_format($c->current_debt_balance, 2) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Register New Customer Button -->
                    <button type="button" 
                            @click="openCustomerModal = true"
                            title="Register New Customer"
                            class="px-2 py-1 bg-white hover:bg-slate-100 border border-slate-300 rounded-lg text-xs text-slate-700 font-semibold shadow-2xs shrink-0">
                        <i class="fa-solid fa-user-plus text-emerald-600"></i>
                    </button>

                    <!-- Prescription Details Toggle Pill -->
                    <button type="button" 
                            @click="rxOpen = !rxOpen" 
                            :class="(rxOpen || doctorName || patientName) ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-100'"
                            class="px-2.5 py-1 border rounded-lg text-[11px] font-semibold flex items-center gap-1 shadow-2xs shrink-0 transition-colors"
                            title="Toggle Prescription Details (Doctor / Patient)">
                        <i class="fa-solid fa-file-prescription text-emerald-600"></i>
                        <span>Rx</span>
                        <i class="fa-solid fa-chevron-down text-[9px] transition-transform" :class="{ 'rotate-180': rxOpen }"></i>
                    </button>
                </div>

                <!-- Customer Credit Alert (Shown if customer selected) -->
                <template x-if="selectedCustomer">
                    <div class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-[11px] flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800" x-text="selectedCustomer.name"></span>
                            <span class="text-[10px] text-slate-500 font-mono" x-text="'(' + selectedCustomer.phone + ')'"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-slate-500">Available Credit:</span>
                            <span class="font-bold font-mono text-xs"
                                  :class="selectedCustomer.remaining_credit < grandTotal ? 'text-rose-600' : 'text-emerald-700'"
                                  x-text="'Rs. ' + parseFloat(selectedCustomer.remaining_credit).toFixed(2)"></span>
                        </div>
                    </div>
                </template>

                <!-- Prescription Inputs (Collapsible) -->
                <div x-show="rxOpen" x-cloak class="grid grid-cols-3 gap-1.5 pt-0.5 text-xs">
                    <input type="text" x-model="doctorName" placeholder="Doctor Name..." class="px-2 py-1 text-xs rounded-lg border border-slate-300 bg-white">
                    <input type="text" x-model="doctorRegNo" placeholder="SLMC Reg No..." class="px-2 py-1 text-xs rounded-lg border border-slate-300 bg-white">
                    <input type="text" x-model="patientName" placeholder="Patient Name..." class="px-2 py-1 text-xs rounded-lg border border-slate-300 bg-white">
                </div>
            </div>

            <!-- Cart Table Header Bar -->
            <div class="px-3 py-1 bg-slate-100/80 border-b border-slate-200/80 flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500 shrink-0">
                <span>Billing Line Items (<span class="text-emerald-700" x-text="cart.length">0</span>)</span>
                <div class="flex items-center gap-6">
                    <span>Unit / Pack Mode & Qty</span>
                    <span class="w-20 text-right">Line Total</span>
                </div>
            </div>

            <!-- Cart Table Items (Maximized Vertical Scroll Space) -->
            <div class="flex-1 overflow-y-auto px-2.5 py-2 min-h-0">
                
                <template x-if="cart.length === 0">
                    <div class="h-full min-h-[140px] flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-solid fa-cart-shopping text-3xl mb-1.5 text-slate-300"></i>
                        <p class="text-xs font-semibold text-slate-500">Billing Cart is Empty</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Scan barcode or click medications on the left to bill</p>
                    </div>
                </template>

                <div class="space-y-1.5">
                    <template x-for="(item, index) in cart" :key="item.product_id + '-' + item.sell_unit + '-' + item.base_unit_price">
                        <div class="bg-slate-50 hover:bg-emerald-50/30 border border-slate-200/90 rounded-xl px-2.5 py-1.5 flex flex-col gap-1 text-xs transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <!-- Item info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-mono font-bold text-slate-400" x-text="(index + 1) + '.'"></span>
                                        <h5 class="font-bold text-slate-800 text-xs truncate" x-text="item.brand_name"></h5>
                                        <span class="text-[9px] bg-slate-200 text-slate-700 px-1 py-0.2 rounded font-mono shrink-0" x-text="item.item_type"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5 flex items-center flex-wrap gap-1.5">
                                        <span class="font-semibold text-slate-700">
                                            Rs. <span x-text="getItemActiveUnitPrice(item).toFixed(2)"></span>
                                            /<span x-text="item.sell_unit === 'pack' ? ('Pack of ' + item.units_per_pack) : (item.base_uom || 'Unit')"></span>
                                        </span>
                                        <template x-if="item.sell_unit === 'pack'">
                                            <span class="text-indigo-700 font-bold bg-indigo-100/80 px-1.5 py-0.2 rounded border border-indigo-200"
                                                  x-text="'= ' + (item.quantity * item.units_per_pack) + ' ' + (item.base_uom || 'Tab')"></span>
                                        </template>
                                        <span class="text-emerald-700 font-semibold bg-emerald-100/60 px-1 rounded">
                                            Batch: <span x-text="item.selected_batch_no || item.earliest_batch_no || 'Active'"></span>
                                        </span>
                                    </div>

                                    <!-- Multi-Batch Price Selector Pills (shown when item has multiple batch prices) -->
                                    <template x-if="item.price_options && item.price_options.length > 1">
                                        <div class="mt-1 flex items-center flex-wrap gap-1">
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-amber-700 flex items-center gap-0.5">
                                                <i class="fa-solid fa-tags text-[8px]"></i> Batch Price:
                                            </span>
                                            <template x-for="opt in item.price_options" :key="opt.price + '-' + opt.batch_id">
                                                <button type="button"
                                                        @click="setItemBatchPrice(index, opt)"
                                                        :class="Math.abs(item.base_unit_price - opt.price) < 0.005 ? 'bg-amber-600 text-white font-bold border-amber-600 shadow-2xs' : 'bg-white text-slate-700 hover:bg-amber-50 border-slate-300'"
                                                        class="px-1.5 py-0.5 rounded-md border text-[10px] font-mono transition-all"
                                                        :title="'Batch: ' + opt.batch_number + ' | Stock: ' + opt.stock_quantity + (opt.expiry_date ? ' | Exp: ' + opt.expiry_date : '')">
                                                    <span x-text="'Rs. ' + (item.sell_unit === 'pack' ? (opt.price * item.units_per_pack) : opt.price).toFixed(2)"></span>
                                                    <span class="text-[8px] opacity-80" x-text="'(' + opt.batch_number + ')'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                <!-- Inline Pack vs Unit Switcher (when units_per_pack > 1) -->
                                <template x-if="item.units_per_pack > 1">
                                    <div class="inline-flex rounded-lg p-0.5 bg-slate-200/80 border border-slate-300/80 text-[10px] shrink-0">
                                        <button type="button"
                                                @click="setItemSellUnit(index, 'unit')"
                                                :class="item.sell_unit === 'unit' ? 'bg-emerald-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                                class="px-1.5 py-0.5 rounded-md transition-all"
                                                :title="'Bill per ' + (item.base_uom || 'Tablet') + ' (Rs. ' + item.base_unit_price.toFixed(2) + ')'"
                                                x-text="item.base_uom || 'Tab'">
                                        </button>
                                        <button type="button"
                                                @click="setItemSellUnit(index, 'pack')"
                                                :class="item.sell_unit === 'pack' ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                                class="px-1.5 py-0.5 rounded-md transition-all"
                                                :title="'Bill per Pack of ' + item.units_per_pack + ' (Rs. ' + (item.base_unit_price * item.units_per_pack).toFixed(2) + ')'"
                                                x-text="'Pack x' + item.units_per_pack">
                                        </button>
                                    </div>
                                </template>

                                <!-- Quantity Adjuster -->
                                <div class="flex items-center space-x-1 shrink-0">
                                    <button type="button" 
                                            @click="decreaseQty(index)" 
                                            class="w-6 h-6 rounded-lg bg-white border border-slate-300 hover:bg-slate-200 flex items-center justify-center text-slate-700 font-bold transition-colors">
                                        -
                                    </button>
                                    <input type="number" 
                                           min="1" 
                                           x-model.number="item.quantity"
                                           @input="validateItemQty(index)"
                                           class="w-11 h-6 text-center text-xs font-bold font-mono bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    <button type="button" 
                                            @click="increaseQty(index)" 
                                            class="w-6 h-6 rounded-lg bg-white border border-slate-300 hover:bg-slate-200 flex items-center justify-center text-slate-700 font-bold transition-colors">
                                        +
                                    </button>
                                </div>

                                <!-- Line Subtotal & Remove -->
                                <div class="w-24 text-right flex items-center justify-end gap-1.5 shrink-0">
                                    <div class="font-bold font-mono text-xs text-slate-900" x-text="'Rs. ' + (item.quantity * getItemActiveUnitPrice(item)).toFixed(2)"></div>
                                    <button type="button" 
                                            @click="removeFromCart(index)" 
                                            title="Remove Item"
                                            class="text-[11px] text-rose-400 hover:text-rose-600 transition-colors p-0.5">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- Compact Financial Calculation & Checkout Box -->
            <div class="px-3 py-2 bg-slate-900 text-white rounded-t-2xl shadow-xl space-y-1.5 shrink-0">
                
                <!-- Top Summary Row: Subtotal + Discount on Left, Grand Total on Right -->
                <div class="flex items-center justify-between gap-3 pb-1.5 border-b border-slate-800">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                        <div class="text-slate-300">
                            <span class="text-slate-400">Subtotal (<span x-text="cart.length"></span>):</span>
                            <span class="font-mono font-semibold text-white ml-1" x-text="'Rs. ' + subtotal.toFixed(2)"></span>
                        </div>

                        <div class="flex items-center gap-1.5 text-slate-300">
                            <span class="text-slate-400">Disc:</span>
                            <input type="number" step="0.5" x-model.number="discountAmount" 
                                   title="Positive for discount, or negative (e.g. -2) to round up bill"
                                   class="w-16 h-5 px-1.5 bg-slate-800 text-[11px] text-right rounded border border-slate-700 font-mono text-emerald-400 focus:outline-none focus:border-emerald-500">
                            <span class="font-mono text-[11px]"
                                  :class="(parseFloat(discountAmount) || 0) < 0 ? 'text-amber-400' : 'text-emerald-400'"
                                  x-text="((parseFloat(discountAmount) || 0) < 0 ? '(+' + Math.abs(parseFloat(discountAmount) || 0).toFixed(2) + ')' : '')"></span>
                        </div>
                    </div>

                    <div class="text-right shrink-0 flex items-baseline gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Grand Total</span>
                        <span class="text-xl font-black font-display text-emerald-400 tracking-tight font-mono" 
                              x-text="'Rs. ' + grandTotal.toFixed(2)">
                        </span>
                    </div>
                </div>

                <!-- Payment Modes (Compact Horizontal Pills) & Cash Tendered Inline -->
                <div class="grid grid-cols-12 gap-1.5 items-center">
                    <div :class="paymentType === 'cash' ? 'col-span-7' : 'col-span-12'" class="grid grid-cols-4 gap-1">
                        <button type="button" 
                                @click="paymentType = 'cash'"
                                :class="paymentType === 'cash' ? 'bg-emerald-600 text-white font-bold ring-1 ring-emerald-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="py-1.5 px-2 rounded-lg text-[11px] flex items-center justify-center gap-1 transition-all">
                            <i class="fa-solid fa-money-bill-wave text-[10px]"></i>
                            <span>Cash</span>
                        </button>

                        <button type="button" 
                                @click="paymentType = 'card'"
                                :class="paymentType === 'card' ? 'bg-sky-600 text-white font-bold ring-1 ring-sky-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="py-1.5 px-2 rounded-lg text-[11px] flex items-center justify-center gap-1 transition-all">
                            <i class="fa-solid fa-credit-card text-[10px]"></i>
                            <span>Card</span>
                        </button>

                        <button type="button" 
                                @click="paymentType = 'qr'"
                                :class="paymentType === 'qr' ? 'bg-teal-600 text-white font-bold ring-1 ring-teal-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="py-1.5 px-2 rounded-lg text-[11px] flex items-center justify-center gap-1 transition-all">
                            <i class="fa-solid fa-qrcode text-[10px]"></i>
                            <span>LankaQR</span>
                        </button>

                        <button type="button" 
                                @click="selectLendingMode()"
                                :class="paymentType === 'credit_lending' ? 'bg-purple-600 text-white font-bold ring-1 ring-purple-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                class="py-1.5 px-2 rounded-lg text-[11px] flex items-center justify-center gap-1 transition-all">
                            <i class="fa-solid fa-handshake text-[10px]"></i>
                            <span>Lending</span>
                        </button>
                    </div>

                    <!-- Compact Inline Cash Tendered & Change (Shown in Cash Mode) -->
                    <template x-if="paymentType === 'cash'">
                        <div class="col-span-5 flex items-center justify-between gap-1.5 bg-slate-800/90 px-2 py-1 rounded-lg border border-slate-700/80 text-[11px]">
                            <div class="flex items-center gap-1 flex-1 min-w-0">
                                <span class="text-[9px] text-slate-400 uppercase font-bold shrink-0">Cash:</span>
                                <input type="number" 
                                       min="0" 
                                       x-model.number="tenderedAmount" 
                                       placeholder="0.00"
                                       class="w-full px-1.5 py-0.5 bg-slate-900 border border-slate-700 rounded text-emerald-400 font-mono font-bold text-xs focus:outline-none focus:border-emerald-500">
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[9px] text-slate-400 uppercase font-bold block leading-none">Change</span>
                                <span class="text-xs font-bold font-mono" 
                                      :class="changeReturn >= 0 ? 'text-teal-400' : 'text-rose-400'"
                                      x-text="changeReturn.toFixed(2)">
                                </span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Action Buttons (Charge & Void) -->
                <div class="grid grid-cols-12 gap-2 pt-0.5">
                    <button type="button" 
                            @click="clearCart()" 
                            class="col-span-3 py-2 rounded-xl bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 text-xs font-semibold flex items-center justify-center gap-1 transition-colors">
                        <i class="fa-solid fa-ban text-[10px]"></i>
                        <span>Void [Esc]</span>
                    </button>

                    <button type="button" 
                            @click="submitCheckout()"
                            :disabled="cart.length === 0 || isProcessing"
                            class="col-span-9 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 disabled:opacity-50 text-white font-bold text-xs tracking-wide flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/40 transition-all cursor-pointer">
                        <template x-if="isProcessing">
                            <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                        </template>
                        <template x-if="!isProcessing">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-receipt"></i>
                                <span>PAY & PRINT BILL [F8]</span>
                            </span>
                        </template>
                    </button>
                </div>

            </div>

        </div>

    </div>

    <!-- MODAL 1: Single-Click Quick Item Creation Modal for Cashiers -->
    <div x-show="openQuickCreateModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl border border-slate-200" @click.outside="openQuickCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-pills text-emerald-600"></i>
                    <span>Quick Drug Registration</span>
                </h3>
                <button @click="openQuickCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form @submit.prevent="saveQuickProduct()" class="space-y-3 mt-3 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Barcode / SKU *</label>
                        <input type="text" x-model="quickForm.barcode_sku" required class="w-full px-2.5 py-1.5 border rounded-lg font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Brand Name *</label>
                        <input type="text" x-model="quickForm.brand_name" required placeholder="e.g. Panadol 500mg" class="w-full px-2.5 py-1.5 border rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Generic Salt (Composition)</label>
                        <input type="text" x-model="quickForm.generic_name" placeholder="e.g. Paracetamol" class="w-full px-2.5 py-1.5 border rounded-lg">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Dosage Strength</label>
                        <input type="text" x-model="quickForm.dosage_strength" placeholder="e.g. 500 mg" class="w-full px-2.5 py-1.5 border rounded-lg">
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Item Type</label>
                        <select x-model="quickForm.item_type" class="w-full px-2 py-1.5 border rounded-lg">
                            <option value="tablet">Tablet</option>
                            <option value="capsule">Capsule</option>
                            <option value="syrup">Syrup</option>
                            <option value="injection">Injection</option>
                            <option value="inhaler">Inhaler</option>
                            <option value="cream">Cream</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Base UOM</label>
                        <input type="text" x-model="quickForm.base_uom" placeholder="Tablet/Bottle" class="w-full px-2 py-1.5 border rounded-lg">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Units / Pack</label>
                        <input type="number" min="1" x-model.number="quickForm.units_per_pack" class="w-full px-2 py-1.5 border rounded-lg font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Category</label>
                        <select x-model="quickForm.category_id" class="w-full px-2 py-1.5 border rounded-lg">
                            <option value="">-- None --</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Initial Batch details for immediate sale -->
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200/80 space-y-2">
                    <div class="font-bold text-emerald-800 text-[11px] uppercase tracking-wider">Initial Batch Details</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-medium text-slate-700">Batch Number *</label>
                            <input type="text" x-model="quickForm.initial_batch_number" required placeholder="e.g. BATCH-01" class="w-full px-2 py-1 border rounded bg-white">
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700">Expiry Date *</label>
                            <input type="date" x-model="quickForm.initial_expiry_date" required class="w-full px-2 py-1 border rounded bg-white">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block font-medium text-slate-700">Stock Qty *</label>
                            <input type="number" x-model.number="quickForm.initial_stock" required class="w-full px-2 py-1 border rounded bg-white">
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700">Cost Price (Rs.) *</label>
                            <input type="number" step="0.01" min="0" x-model.number="quickForm.cost_price" required class="w-full px-2 py-1 border rounded bg-white">
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700">Retail Price (Rs.) *</label>
                            <input type="number" step="0.01" min="0" x-model.number="quickForm.retail_price" required class="w-full px-2 py-1 border rounded bg-white">
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <label class="flex items-center space-x-1.5 cursor-pointer">
                        <input type="checkbox" x-model="quickForm.requires_prescription" class="rounded text-emerald-600">
                        <span>Requires Prescription (Rx)</span>
                    </label>
                    <label class="flex items-center space-x-1.5 cursor-pointer">
                        <input type="checkbox" x-model="quickForm.is_controlled" class="rounded text-purple-600">
                        <span>Controlled Substance</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button type="button" @click="openQuickCreateModal = false" class="px-3 py-1.5 border rounded-lg text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm">Save & Index Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Quick Register Customer Modal -->
    <div x-show="openCustomerModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200" @click.outside="openCustomerModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-emerald-600"></i>
                    <span>Register Debtor / Customer</span>
                </h3>
                <button @click="openCustomerModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form @submit.prevent="saveCustomer()" class="space-y-3 mt-3 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" x-model="customerForm.name" required class="w-full px-2.5 py-1.5 border rounded-lg">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Phone Number *</label>
                        <input type="text" x-model="customerForm.phone" required placeholder="+94 7X..." class="w-full px-2.5 py-1.5 border rounded-lg">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">NIC Number</label>
                        <input type="text" x-model="customerForm.nic_number" class="w-full px-2.5 py-1.5 border rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Approved Credit Limit (Rs.) *</label>
                    <input type="number" min="0" step="100" x-model.number="customerForm.credit_limit" required class="w-full px-2.5 py-1.5 border rounded-lg font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Address</label>
                    <textarea x-model="customerForm.address" rows="2" class="w-full px-2.5 py-1.5 border rounded-lg"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t">
                    <button type="button" @click="openCustomerModal = false" class="px-3 py-1.5 border rounded-lg text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Thermal Receipt Preview Modal -->
    <div x-show="showReceiptModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-4 shadow-2xl border border-slate-200" @click.outside="showReceiptModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="font-bold text-xs uppercase tracking-wide text-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-print text-emerald-600"></i> Thermal Receipt Preview (80mm)
                </span>
                <button @click="showReceiptModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <!-- Receipt IFrame Container for Instant Print Without Lag -->
            <div class="mt-2 h-96 border rounded-lg bg-slate-50 overflow-hidden">
                <iframe :src="currentReceiptUrl" class="w-full h-full border-0"></iframe>
            </div>

            <div class="flex justify-end gap-2 mt-3 pt-2 border-t">
                <button type="button" @click="showReceiptModal = false" class="px-3 py-1.5 text-xs border rounded-lg text-slate-600">Close</button>
                <button type="button" @click="printReceiptIframe()" class="px-4 py-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg flex items-center gap-1.5">
                    <i class="fa-solid fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: Multi-Batch Price Selection Modal -->
    <div x-show="openBatchPriceModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200" @click.outside="openBatchPriceModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-display font-bold text-base text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-tags text-amber-600"></i>
                        <span>Select Batch Price</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Multiple batch prices found for <strong class="text-slate-800" x-text="pendingPriceProduct ? pendingPriceProduct.brand_name : ''"></strong>
                    </p>
                </div>
                <button @click="openBatchPriceModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <div class="mt-3 space-y-2 max-h-72 overflow-y-auto" x-show="pendingPriceProduct">
                <template x-for="(opt, idx) in (pendingPriceProduct ? pendingPriceProduct.price_options : [])" :key="opt.price + '-' + opt.batch_id + '-' + idx">
                    <button type="button"
                            @click="confirmBatchPriceSelection(opt)"
                            class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all flex items-center justify-between group">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-800 font-mono bg-slate-100 group-hover:bg-white px-2 py-0.5 rounded border border-slate-200"
                                      x-text="'Batch: ' + (opt.batch_number || 'Default')"></span>
                                <span class="text-[11px] font-semibold"
                                      :class="opt.stock_quantity > 0 ? 'text-emerald-700' : 'text-rose-600'"
                                      x-text="'Stock: ' + opt.stock_quantity + ' ' + (pendingPriceProduct?.base_uom || 'Units')"></span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono mt-1" x-show="opt.expiry_date">
                                Expiry: <span x-text="opt.expiry_date"></span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="font-display font-black text-base text-emerald-700 group-hover:text-emerald-800 font-mono"
                                 x-text="'Rs. ' + (pendingPriceUnit === 'pack' && pendingPriceProduct?.units_per_pack > 1 ? (opt.price * pendingPriceProduct.units_per_pack) : opt.price).toFixed(2)">
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono"
                                 x-text="'per ' + (pendingPriceUnit === 'pack' && pendingPriceProduct?.units_per_pack > 1 ? ('Pack of ' + pendingPriceProduct.units_per_pack) : (pendingPriceProduct?.base_uom || 'Unit'))">
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            <div class="flex justify-end mt-4 pt-2 border-t border-slate-100">
                <button type="button" @click="openBatchPriceModal = false" class="px-3 py-1.5 text-xs border rounded-lg text-slate-600 hover:bg-slate-50">Cancel</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function posTerminal() {
    return {
        wideBilling: false,
        barcodeQuery: '',
        searchQuery: '',
        selectedCategory: null,
        products: [],
        loadingProducts: false,
        cart: [],
        selectedCustomerId: '',
        selectedCustomer: null,
        doctorName: '',
        doctorRegNo: '',
        patientName: '',
        discountAmount: 0,
        paymentType: 'cash',
        tenderedAmount: '',
        isProcessing: false,
        nextInvoicePreview: 'INV-' + Math.floor(1000 + Math.random() * 9000),

        // Modals
        openQuickCreateModal: false,
        openCustomerModal: false,
        showReceiptModal: false,
        openBatchPriceModal: false,
        pendingPriceProduct: null,
        pendingPriceUnit: null,
        currentReceiptUrl: '',

        quickForm: {
            barcode_sku: '',
            brand_name: '',
            generic_name: '',
            dosage_strength: '',
            item_type: 'tablet',
            category_id: '',
            base_uom: 'Tablet',
            units_per_pack: 1,
            initial_batch_number: 'BATCH-01',
            initial_expiry_date: '',
            initial_stock: 50,
            cost_price: 0,
            retail_price: 0,
            requires_prescription: false,
            is_controlled: false,
        },

        customerForm: {
            name: '',
            phone: '',
            nic_number: '',
            credit_limit: 15000,
            address: '',
        },

        customersList: @json($customers),

        init() {
            this.performSearch();
            this.focusBarcode();
        },

        focusBarcode() {
            this.$nextTick(() => {
                if (this.$refs.barcodeInput) this.$refs.barcodeInput.focus();
            });
        },

        focusSearch() {
            if (this.$refs.searchInput) this.$refs.searchInput.focus();
        },

        handleEscape() {
            if (this.openBatchPriceModal) {
                this.openBatchPriceModal = false;
                return;
            }
            if (this.openQuickCreateModal) {
                this.openQuickCreateModal = false;
                return;
            }
            if (this.openCustomerModal) {
                this.openCustomerModal = false;
                return;
            }
            if (this.showReceiptModal) {
                this.showReceiptModal = false;
                return;
            }
            this.clearCart();
        },

        openPaymentModal() {
            if (this.cart.length > 0) {
                this.submitCheckout();
            }
        },

        onCustomerChange() {
            if (!this.selectedCustomerId) {
                this.selectedCustomer = null;
                return;
            }
            this.selectedCustomer = this.customersList.find(c => c.id == this.selectedCustomerId) || null;
        },

        selectLendingMode() {
            if (!this.selectedCustomerId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Customer Required',
                    text: 'Please select a registered customer to bill as Credit / Lending.',
                    confirmButtonColor: '#059669'
                });
                return;
            }
            this.paymentType = 'credit_lending';
        },

        // Fast AJAX Search (sub-150ms)
        async performSearch() {
            this.loadingProducts = true;
            try {
                let params = new URLSearchParams();
                if (this.searchQuery) params.append('q', this.searchQuery);
                if (this.selectedCategory) params.append('category_id', this.selectedCategory);

                const res = await fetch(`/pos/search?${params.toString()}`);
                const data = await res.json();
                if (data.success) {
                    this.products = data.products;
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.loadingProducts = false;
            }
        },

        // Fast Barcode Scanner Lookup (sub-50ms)
        async scanBarcode() {
            const code = this.barcodeQuery.trim();
            if (!code) return;

            try {
                const res = await fetch(`/pos/barcode?barcode=${encodeURIComponent(code)}`);
                const data = await res.json();

                if (data.success) {
                    this.addToCart(data.product);
                    this.barcodeQuery = '';
                } else if (data.not_found) {
                    this.quickForm.barcode_sku = code;
                    this.openQuickCreateModal = true;
                    this.barcodeQuery = '';
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Scan Warning',
                        text: data.message,
                        timer: 2500,
                        showConfirmButton: false
                    });
                    this.barcodeQuery = '';
                }
            } catch (err) {
                console.error(err);
            }
        },

        addToCart(product, preferredUnit = null, selectedPriceOption = null) {
            const unitsPerPack = Math.max(1, parseInt(product.units_per_pack) || 1);
            const targetUnit = (preferredUnit === 'pack' && unitsPerPack > 1) ? 'pack' : 'unit';
            const priceOptions = Array.isArray(product.price_options) ? product.price_options : [];

            // If product has multiple distinct batch prices and user hasn't picked one yet, prompt selection
            if (!selectedPriceOption && priceOptions.length > 1) {
                this.pendingPriceProduct = product;
                this.pendingPriceUnit = targetUnit;
                this.openBatchPriceModal = true;
                return;
            }

            const chosenOption = selectedPriceOption || (priceOptions.length === 1 ? priceOptions[0] : null);
            const basePrice = chosenOption
                ? (parseFloat(chosenOption.price) || 0)
                : (parseFloat(product.retail_price) || 0);
            const selectedBatchId = chosenOption ? (chosenOption.batch_id || null) : null;
            const selectedBatchNo = chosenOption ? (chosenOption.batch_number || product.earliest_batch_no) : product.earliest_batch_no;
            const selectedExpiry = (chosenOption && chosenOption.expiry_date) ? chosenOption.expiry_date : product.earliest_expiry;

            // Look for an existing cart entry with the same product_id, sell_unit, and base_unit_price
            const existingIndex = this.cart.findIndex(
                i => i.product_id === product.id && i.sell_unit === targetUnit && Math.abs(i.base_unit_price - basePrice) < 0.005
            );

            if (existingIndex > -1) {
                this.cart[existingIndex].quantity++;
            } else {
                this.cart.push({
                    product_id: product.id,
                    brand_name: product.brand_name,
                    item_type: product.item_type,
                    base_uom: product.base_uom || 'Tablet',
                    units_per_pack: unitsPerPack,
                    sell_unit: targetUnit,
                    base_unit_price: basePrice,
                    unit_price: basePrice,
                    price_options: priceOptions,
                    selected_batch_id: selectedBatchId,
                    selected_batch_no: selectedBatchNo,
                    quantity: 1,
                    total_stock: product.total_stock,
                    earliest_expiry: selectedExpiry,
                    earliest_batch_no: product.earliest_batch_no,
                    requires_prescription: product.requires_prescription,
                    is_controlled: product.is_controlled,
                });
            }

            this.focusBarcode();
        },

        confirmBatchPriceSelection(option) {
            if (!this.pendingPriceProduct) return;
            const prod = this.pendingPriceProduct;
            const unit = this.pendingPriceUnit;
            this.openBatchPriceModal = false;
            this.pendingPriceProduct = null;
            this.pendingPriceUnit = null;
            this.addToCart(prod, unit, option);
        },

        setItemBatchPrice(index, option) {
            const item = this.cart[index];
            if (!item || !option) return;
            const newPrice = parseFloat(option.price) || 0;
            if (Math.abs(item.base_unit_price - newPrice) < 0.005) return;

            // Check if another cart line already exists for this product + sell_unit + newPrice
            const duplicateIndex = this.cart.findIndex(
                (c, idx) => idx !== index && c.product_id === item.product_id && c.sell_unit === item.sell_unit && Math.abs(c.base_unit_price - newPrice) < 0.005
            );
            if (duplicateIndex > -1) {
                this.cart[duplicateIndex].quantity += item.quantity;
                this.cart.splice(index, 1);
            } else {
                item.base_unit_price = newPrice;
                item.unit_price = newPrice;
                item.selected_batch_id = option.batch_id || null;
                item.selected_batch_no = option.batch_number || item.earliest_batch_no;
                if (option.expiry_date) {
                    item.earliest_expiry = option.expiry_date;
                }
            }
        },

        getItemActiveUnitPrice(item) {
            const basePrice = parseFloat(item.base_unit_price) || 0;
            const packSize = Math.max(1, parseInt(item.units_per_pack) || 1);
            return item.sell_unit === 'pack' ? (basePrice * packSize) : basePrice;
        },

        setItemSellUnit(index, unitMode) {
            const item = this.cart[index];
            if (!item) return;
            const targetMode = (unitMode === 'pack' && item.units_per_pack > 1) ? 'pack' : 'unit';
            if (item.sell_unit === targetMode) return;

            // Check if another cart line already exists for this product + targetMode + same price
            const duplicateIndex = this.cart.findIndex(
                (c, idx) => idx !== index && c.product_id === item.product_id && c.sell_unit === targetMode && Math.abs(c.base_unit_price - item.base_unit_price) < 0.005
            );
            if (duplicateIndex > -1) {
                this.cart[duplicateIndex].quantity += item.quantity;
                this.cart.splice(index, 1);
            } else {
                item.sell_unit = targetMode;
            }
        },

        increaseQty(index) {
            this.cart[index].quantity++;
        },

        decreaseQty(index) {
            if (this.cart[index].quantity > 1) {
                this.cart[index].quantity--;
            } else {
                this.removeFromCart(index);
            }
        },

        validateItemQty(index) {
            const item = this.cart[index];
            if (!item.quantity || item.quantity < 1) {
                item.quantity = 1;
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        clearCart() {
            if (this.cart.length === 0) return;
            Swal.fire({
                title: 'Void Active Bill?',
                text: 'All items will be cleared from counter cart.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Yes, Clear Cart'
            }).then((res) => {
                if (res.isConfirmed) {
                    this.cart = [];
                    this.tenderedAmount = '';
                    this.discountAmount = 0;
                    this.focusBarcode();
                }
            });
        },

        // Financial Getters
        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.quantity * this.getItemActiveUnitPrice(item)), 0);
        },

        get grandTotal() {
            const total = this.subtotal - (parseFloat(this.discountAmount) || 0);
            return Math.max(0, total);
        },

        get changeReturn() {
            const tendered = parseFloat(this.tenderedAmount) || 0;
            return tendered - this.grandTotal;
        },

        // Checkout & Transaction Execution
        async submitCheckout() {
            if (this.cart.length === 0) return;

            // Check if Rx or controlled item is in cart
            const hasControlled = this.cart.some(i => i.is_controlled);
            if (hasControlled && (!this.doctorName || !this.patientName)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Controlled Substance Warning',
                    text: 'Doctor Name and Patient Name are legally required for controlled substances.',
                    confirmButtonColor: '#059669'
                });
                return;
            }

            // Check lending limit
            if (this.paymentType === 'credit_lending') {
                if (!this.selectedCustomer) {
                    this.selectLendingMode();
                    return;
                }
                const projectedDebt = parseFloat(this.selectedCustomer.current_debt_balance) + this.grandTotal;
                if (projectedDebt > parseFloat(this.selectedCustomer.credit_limit)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Credit Limit Exceeded',
                        text: `Customer limit is Rs. ${this.selectedCustomer.credit_limit}. Current debt is Rs. ${this.selectedCustomer.current_debt_balance}. This bill would exceed their limit.`,
                        confirmButtonColor: '#059669'
                    });
                    return;
                }
            }

            this.isProcessing = true;

            const payload = {
                items: this.cart.map(i => ({
                    product_id: i.product_id,
                    quantity: i.sell_unit === 'pack' ? (i.quantity * i.units_per_pack) : i.quantity,
                    unit_price: i.base_unit_price,
                    batch_id: i.selected_batch_id || null,
                })),
                customer_id: this.selectedCustomerId || null,
                payment_type: this.paymentType,
                amount_paid: this.paymentType === 'cash' ? (parseFloat(this.tenderedAmount) || this.grandTotal) : this.grandTotal,
                discount_amount: parseFloat(this.discountAmount) || 0,
                tax_amount: 0,
                doctor_name: this.doctorName || null,
                doctor_reg_no: this.doctorRegNo || null,
                patient_name: this.patientName || null,
            };

            try {
                const res = await fetch('/pos/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (data.success) {
                    // Show receipt
                    this.currentReceiptUrl = data.receipt_url;
                    this.showReceiptModal = true;

                    // Reset cart and fields
                    this.cart = [];
                    this.tenderedAmount = '';
                    this.discountAmount = 0;
                    this.doctorName = '';
                    this.doctorRegNo = '';
                    this.patientName = '';
                    this.selectedCustomerId = '';
                    this.selectedCustomer = null;
                    this.nextInvoicePreview = 'INV-' + Math.floor(1000 + Math.random() * 9000);

                    // Refresh products stock
                    this.performSearch();

                    // Modern Toast
                    Swal.fire({
                        icon: 'success',
                        title: 'Invoice #' + data.invoice_number + ' Completed',
                        text: 'Change Return: Rs. ' + (data.change_return || 0).toFixed(2),
                        timer: 3000,
                        showConfirmButton: false,
                        position: 'top-end',
                        toast: true
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Checkout Error',
                        text: data.message,
                        confirmButtonColor: '#059669'
                    });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'An unexpected network error occurred.',
                    confirmButtonColor: '#059669'
                });
            } finally {
                this.isProcessing = false;
            }
        },

        printReceiptIframe() {
            const iframe = document.querySelector('iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.print();
            }
        },

        // Quick Product Save
        async saveQuickProduct() {
            try {
                const res = await fetch('/pos/quick-store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.quickForm)
                });

                const data = await res.json();

                if (data.success) {
                    this.openQuickCreateModal = false;
                    this.addToCart(data.product);
                    this.performSearch();

                    Swal.fire({
                        icon: 'success',
                        title: 'Item Created & Added',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            } catch (err) {
                console.error(err);
            }
        },

        // Quick Customer Save
        async saveCustomer() {
            try {
                const res = await fetch('/customers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.customerForm)
                });

                const data = await res.json();

                if (data.success) {
                    this.customersList.push(data.customer);
                    this.selectedCustomerId = data.customer.id;
                    this.onCustomerChange();
                    this.openCustomerModal = false;

                    Swal.fire({
                        icon: 'success',
                        title: 'Customer Added',
                        text: `Customer ${data.customer.name} registered with Rs. ${data.customer.credit_limit} credit limit.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }
    };
}
</script>
@endpush
