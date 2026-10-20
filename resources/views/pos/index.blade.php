@extends('layouts.app')

@section('title', 'Fast Counter POS Terminal')

@section('content')
<div class="h-[calc(100vh-4.2rem)] flex flex-col p-3 gap-3 overflow-hidden" 
     x-data="posTerminal()"
     x-init="init()"
     @keydown.window.f2.prevent="focusSearch()"
     @keydown.window.f8.prevent="openPaymentModal()"
     @keydown.window.escape.prevent="handleEscape()">

    <!-- Top Shift & Status Bar -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 px-4 py-2 flex items-center justify-between shrink-0">
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-slate-700 tracking-wide uppercase">Register Online</span>
                <span class="text-xs text-slate-400">|</span>
                <span class="text-xs text-slate-500 font-mono">Invoice: <strong class="text-slate-900" x-text="nextInvoicePreview">INV-PENDING</strong></span>
            </div>

            <!-- Quick Hotkey Pills -->
            <div class="hidden lg:flex items-center space-x-2 text-[11px] font-medium text-slate-500">
                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200"><strong class="font-mono text-emerald-600">F2</strong> Search</span>
                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200"><strong class="font-mono text-emerald-600">F8</strong> Pay / Checkout</span>
                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200"><strong class="font-mono text-rose-600">Esc</strong> Clear / Close</span>
            </div>
        </div>

        <!-- Shift Metrics -->
        <div class="flex items-center space-x-4 text-xs font-semibold">
            <div class="text-slate-600">
                Today Bills: <span class="font-bold text-slate-900">{{ $stats['today_sales_count'] }}</span>
            </div>
            <div class="text-slate-600">
                Today Revenue: <span class="font-bold text-emerald-600">Rs. {{ number_format($stats['today_revenue'], 2) }}</span>
            </div>
            <div class="text-slate-600">
                Cash Drawer: <span class="font-bold text-teal-600">Rs. {{ number_format($stats['today_cash'], 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Main POS Grid (Left: Catalog/Search, Right: Live Cart & Bill) -->
    <div class="flex-1 grid grid-cols-12 gap-3 min-h-0">

        <!-- LEFT PANEL: Search, Filter & Medicine Grid (Col 7) -->
        <div class="col-span-12 lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
            
            <!-- Barcode & Search Controls -->
            <div class="p-3 border-b border-slate-200 bg-slate-50/50 space-y-2">
                <div class="grid grid-cols-12 gap-2">
                    <!-- Barcode Scanner Input (Auto-Focusable) -->
                    <div class="col-span-5 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-barcode text-base text-emerald-600"></i>
                        </div>
                        <input type="text" 
                               x-ref="barcodeInput"
                               x-model="barcodeQuery"
                               @keydown.enter.prevent="scanBarcode()"
                               placeholder="Scan Barcode (Enter)..." 
                               class="w-full pl-9 pr-3 py-2 bg-white text-xs font-mono font-medium rounded-xl border border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400 shadow-xs">
                    </div>

                    <!-- Keyword Search (Brand / Generic Salt) -->
                    <div class="col-span-5 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </div>
                        <input type="text" 
                               x-ref="searchInput"
                               x-model="searchQuery"
                               @input.debounce.150ms="performSearch()"
                               placeholder="Search Brand or Generic Salt [F2]..." 
                               class="w-full pl-8 pr-8 py-2 bg-white text-xs font-medium rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400 shadow-xs">
                        <button x-show="searchQuery" @click="searchQuery = ''; performSearch()" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-xs">
                            <i class="fa-solid fa-times-circle"></i>
                        </button>
                    </div>

                    <!-- Quick Add Product Button -->
                    <div class="col-span-2">
                        <button type="button" 
                                @click="openQuickCreateModal = true"
                                class="w-full h-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-1 shadow-xs transition-colors">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>New Item</span>
                        </button>
                    </div>
                </div>

                <!-- Therapeutic Category Pill Filters -->
                <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
                    <button type="button" 
                            @click="selectedCategory = null; performSearch()"
                            :class="selectedCategory === null ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-[11px] whitespace-nowrap transition-colors">
                        All Categories
                    </button>
                    @foreach($categories as $cat)
                    <button type="button" 
                            @click="selectedCategory = {{ $cat->id }}; performSearch()"
                            :class="selectedCategory === {{ $cat->id }} ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-[11px] whitespace-nowrap transition-colors">
                        {{ $cat->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Cards Stream (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-3 relative">
                
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
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-3 gap-2.5">
                    <template x-for="product in products" :key="product.id">
                        <div @click="addToCart(product)"
                             class="group relative bg-white hover:bg-emerald-50/40 rounded-xl border border-slate-200 hover:border-emerald-400 p-2.5 flex flex-col justify-between cursor-pointer transition-all shadow-xs hover:shadow-md select-none">
                            
                            <!-- Badges Header -->
                            <div class="flex items-start justify-between gap-1 mb-1">
                                <span class="text-[10px] font-mono font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200" x-text="product.item_type"></span>
                                
                                <div class="flex items-center gap-1">
                                    <template x-if="product.requires_prescription">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 border border-rose-200 uppercase tracking-wider">Rx</span>
                                    </template>
                                    <template x-if="product.is_controlled">
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 border border-purple-200 uppercase tracking-wider">Ctrl</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Drug Brand Name & Composition -->
                            <div class="mb-2">
                                <h4 class="font-bold text-slate-800 text-xs group-hover:text-emerald-700 line-clamp-1" x-text="product.brand_name"></h4>
                                <p class="text-[11px] text-slate-500 font-medium italic line-clamp-1" x-text="product.generic_name"></p>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="product.dosage_strength || product.barcode_sku"></p>
                            </div>

                            <!-- Stock & FEFO Batch Badge -->
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-display font-bold text-sm text-emerald-700" x-text="'Rs. ' + parseFloat(product.retail_price).toFixed(2)"></span>
                                </div>

                                <div class="text-right">
                                    <template x-if="product.total_stock > 0">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] font-semibold text-slate-600" x-text="product.total_stock + ' ' + (product.base_uom || 'units')"></span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" title="In Stock"></span>
                                        </div>
                                    </template>
                                    <template x-if="product.total_stock <= 0">
                                        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-1 py-0.5 rounded border border-rose-200">OUT OF STOCK</span>
                                    </template>

                                    <!-- Earliest Expiry FEFO Badge -->
                                    <template x-if="product.earliest_expiry">
                                        <span class="text-[9px] text-slate-400 block font-mono">Exp: <span x-text="product.earliest_expiry"></span></span>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- RIGHT PANEL: Live Checkout Cart & Billing Register (Col 5) -->
        <div class="col-span-12 lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
            
            <!-- Customer & Prescription Header -->
            <div class="p-3 border-b border-slate-200 bg-slate-50/70 space-y-2.5">
                
                <!-- Customer Selection -->
                <div class="flex items-center gap-2">
                    <div class="flex-1 relative">
                        <select x-model="selectedCustomerId" 
                                @change="onCustomerChange()"
                                class="w-full pl-3 pr-8 py-1.5 bg-white text-xs font-semibold rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="">-- Walk-in Customer (Cash / Card / QR) --</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }} ({{ $c->phone }}) - Debt: Rs. {{ number_format($c->current_debt_balance, 2) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" 
                            @click="openCustomerModal = true"
                            title="Register New Customer"
                            class="px-2.5 py-1.5 bg-white hover:bg-slate-100 border border-slate-300 rounded-xl text-xs text-slate-700 font-semibold shadow-xs">
                        <i class="fa-solid fa-user-plus text-emerald-600"></i>
                    </button>
                </div>

                <!-- Customer Credit Alert (Shown if customer selected) -->
                <template x-if="selectedCustomer">
                    <div class="p-2 rounded-xl bg-slate-100 border border-slate-200 text-xs flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800" x-text="selectedCustomer.name"></span>
                            <span class="text-[10px] text-slate-500 block font-mono" x-text="'Tel: ' + selectedCustomer.phone"></span>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-500">Remaining Credit:</div>
                            <span class="font-bold font-mono text-xs"
                                  :class="selectedCustomer.remaining_credit < grandTotal ? 'text-rose-600' : 'text-emerald-700'"
                                  x-text="'Rs. ' + parseFloat(selectedCustomer.remaining_credit).toFixed(2)"></span>
                        </div>
                    </div>
                </template>

                <!-- Prescription Details Toggle -->
                <div x-data="{ rxOpen: false }" class="border-t border-slate-200/80 pt-1.5">
                    <button type="button" 
                            @click="rxOpen = !rxOpen" 
                            class="text-[11px] font-semibold text-slate-600 hover:text-emerald-700 flex items-center justify-between w-full">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-solid fa-file-prescription text-emerald-600"></i>
                            <span>Prescription Details (Doctor / Patient)</span>
                        </span>
                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': rxOpen }"></i>
                    </button>

                    <div x-show="rxOpen" x-cloak class="mt-2 grid grid-cols-2 gap-2 text-xs">
                        <input type="text" x-model="doctorName" placeholder="Doctor Name..." class="px-2 py-1 text-xs rounded-lg border border-slate-300 bg-white">
                        <input type="text" x-model="doctorRegNo" placeholder="Doctor SLMC No..." class="px-2 py-1 text-xs rounded-lg border border-slate-300 bg-white">
                        <input type="text" x-model="patientName" placeholder="Patient Name..." class="col-span-2 px-2 py-1 text-xs rounded-lg border border-slate-300 bg-white">
                    </div>
                </div>

            </div>

            <!-- Cart Table Items -->
            <div class="flex-1 overflow-y-auto p-2">
                
                <template x-if="cart.length === 0">
                    <div class="h-48 flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-solid fa-cart-shopping text-3xl mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold text-slate-500">Cart is empty</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Scan barcode or click items to add to bill</p>
                    </div>
                </template>

                <div class="space-y-1.5">
                    <template x-for="(item, index) in cart" :key="item.product_id">
                        <div class="bg-slate-50 hover:bg-slate-100/80 border border-slate-200/90 rounded-xl p-2 flex items-center justify-between text-xs transition-colors">
                            
                            <!-- Item info -->
                            <div class="flex-1 min-w-0 pr-2">
                                <div class="flex items-center gap-1.5">
                                    <h5 class="font-bold text-slate-800 text-xs truncate" x-text="item.brand_name"></h5>
                                    <span class="text-[9px] bg-slate-200 text-slate-700 px-1 py-0.2 rounded font-mono" x-text="item.item_type"></span>
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono mt-0.5 flex items-center gap-2">
                                    <span>Rs. <span x-text="parseFloat(item.unit_price).toFixed(2)"></span></span>
                                    <span class="text-emerald-700 font-semibold bg-emerald-100/60 px-1 rounded">FEFO: <span x-text="item.earliest_expiry || 'Active'"></span></span>
                                </div>
                            </div>

                            <!-- Quantity Adjuster -->
                            <div class="flex items-center space-x-1">
                                <button type="button" 
                                        @click="decreaseQty(index)" 
                                        class="w-6 h-6 rounded-lg bg-white border border-slate-300 hover:bg-slate-200 flex items-center justify-center text-slate-700 font-bold transition-colors">
                                    -
                                </button>
                                <input type="number" 
                                       min="1" 
                                       :max="item.total_stock"
                                       x-model.number="item.quantity"
                                       @input="validateItemQty(index)"
                                       class="w-12 h-6 text-center text-xs font-bold font-mono bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <button type="button" 
                                        @click="increaseQty(index)" 
                                        class="w-6 h-6 rounded-lg bg-white border border-slate-300 hover:bg-slate-200 flex items-center justify-center text-slate-700 font-bold transition-colors">
                                    +
                                </button>
                            </div>

                            <!-- Line Subtotal & Remove -->
                            <div class="w-20 text-right pl-2">
                                <div class="font-bold font-mono text-xs text-slate-900" x-text="'Rs. ' + (item.quantity * item.unit_price).toFixed(2)"></div>
                                <button type="button" 
                                        @click="removeFromCart(index)" 
                                        class="text-[10px] text-rose-500 hover:text-rose-700 transition-colors">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>

                        </div>
                    </template>
                </div>

            </div>

            <!-- Financial Calculation Box -->
            <div class="p-3 bg-slate-900 text-white rounded-t-2xl shadow-xl space-y-2.5">
                
                <!-- Calculation Breakdown Rows -->
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between text-slate-300">
                        <span>Subtotal (<span x-text="cart.length"></span> items)</span>
                        <span class="font-mono" x-text="'Rs. ' + subtotal.toFixed(2)"></span>
                    </div>

                    <div class="flex justify-between items-center text-slate-300">
                        <span class="flex items-center gap-1">
                            Discount
                            <input type="number" min="0" step="0.5" x-model.number="discountAmount" 
                                   class="w-16 h-5 px-1 bg-slate-800 text-[11px] text-right rounded border border-slate-700 font-mono text-emerald-400">
                        </span>
                        <span class="font-mono text-emerald-400" x-text="'- Rs. ' + (discountAmount || 0).toFixed(2)"></span>
                    </div>

                    <div class="flex justify-between items-center text-slate-300">
                        <span>Tax / VAT (0%)</span>
                        <span class="font-mono">Rs. 0.00</span>
                    </div>
                </div>

                <!-- Grand Total Display -->
                <div class="pt-2 border-t border-slate-800 flex items-baseline justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Grand Total</span>
                        <div class="text-[10px] text-emerald-400 font-mono font-medium">LKR (Rs.)</div>
                    </div>
                    <div class="text-2xl font-black font-display text-emerald-400 tracking-tight" 
                         x-text="'Rs. ' + grandTotal.toFixed(2)">
                    </div>
                </div>

                <!-- Payment Modes (Buttons) -->
                <div class="grid grid-cols-4 gap-1.5 pt-1">
                    <button type="button" 
                            @click="paymentType = 'cash'"
                            :class="paymentType === 'cash' ? 'bg-emerald-600 text-white font-bold ring-2 ring-emerald-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                            class="py-1.5 rounded-xl text-xs flex flex-col items-center justify-center transition-all">
                        <i class="fa-solid fa-money-bill-wave mb-0.5"></i>
                        <span>Cash</span>
                    </button>

                    <button type="button" 
                            @click="paymentType = 'card'"
                            :class="paymentType === 'card' ? 'bg-sky-600 text-white font-bold ring-2 ring-sky-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                            class="py-1.5 rounded-xl text-xs flex flex-col items-center justify-center transition-all">
                        <i class="fa-solid fa-credit-card mb-0.5"></i>
                        <span>Card</span>
                    </button>

                    <button type="button" 
                            @click="paymentType = 'qr'"
                            :class="paymentType === 'qr' ? 'bg-teal-600 text-white font-bold ring-2 ring-teal-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                            class="py-1.5 rounded-xl text-xs flex flex-col items-center justify-center transition-all">
                        <i class="fa-solid fa-qrcode mb-0.5"></i>
                        <span>LankaQR</span>
                    </button>

                    <button type="button" 
                            @click="selectLendingMode()"
                            :class="paymentType === 'credit_lending' ? 'bg-purple-600 text-white font-bold ring-2 ring-purple-400' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                            class="py-1.5 rounded-xl text-xs flex flex-col items-center justify-center transition-all">
                        <i class="fa-solid fa-handshake mb-0.5"></i>
                        <span>Lending</span>
                    </button>
                </div>

                <!-- Cash Tender & Change Return (Shown in Cash Mode) -->
                <template x-if="paymentType === 'cash'">
                    <div class="grid grid-cols-2 gap-2 bg-slate-800/80 p-2 rounded-xl border border-slate-700/80 text-xs">
                        <div>
                            <label class="text-[10px] text-slate-400 uppercase font-semibold">Tendered Cash</label>
                            <input type="number" 
                                   min="0" 
                                   x-model.number="tenderedAmount" 
                                   placeholder="0.00"
                                   class="w-full mt-0.5 px-2 py-1 bg-slate-900 border border-slate-700 rounded-lg text-emerald-400 font-mono font-bold text-sm focus:outline-none focus:border-emerald-500">
                        </div>
                        <div class="text-right">
                            <label class="text-[10px] text-slate-400 uppercase font-semibold">Change Return</label>
                            <div class="text-sm font-bold font-mono mt-1" 
                                 :class="changeReturn >= 0 ? 'text-teal-400' : 'text-rose-400'"
                                 x-text="'Rs. ' + changeReturn.toFixed(2)">
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Action Buttons (Charge & Void) -->
                <div class="grid grid-cols-12 gap-2 pt-1">
                    <button type="button" 
                            @click="clearCart()" 
                            class="col-span-3 py-2.5 rounded-xl bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 text-xs font-semibold flex items-center justify-center gap-1 transition-colors">
                        <i class="fa-solid fa-ban text-[10px]"></i>
                        <span>Void [Esc]</span>
                    </button>

                    <button type="button" 
                            @click="submitCheckout()"
                            :disabled="cart.length === 0 || isProcessing"
                            class="col-span-9 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 disabled:opacity-50 text-white font-bold text-sm tracking-wide flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/40 transition-all cursor-pointer">
                        <template x-if="isProcessing">
                            <i class="fa-solid fa-circle-notch fa-spin text-base"></i>
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

                <div class="grid grid-cols-3 gap-2">
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
                            <input type="number" min="1" x-model.number="quickForm.initial_stock" required class="w-full px-2 py-1 border rounded bg-white">
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

</div>
@endsection

@push('scripts')
<script>
function posTerminal() {
    return {
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
        currentReceiptUrl: '',

        quickForm: {
            barcode_sku: '',
            brand_name: '',
            generic_name: '',
            dosage_strength: '',
            item_type: 'tablet',
            category_id: '',
            base_uom: 'Tablet',
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

        addToCart(product) {
            if (product.total_stock <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Out of Stock',
                    text: `${product.brand_name} has 0 available unexpired units.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            const existingIndex = this.cart.findIndex(i => i.product_id === product.id);

            if (existingIndex > -1) {
                if (this.cart[existingIndex].quantity < product.total_stock) {
                    this.cart[existingIndex].quantity++;
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Stock Limit Reached',
                        text: `Cannot add more than available unexpired stock (${product.total_stock}).`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } else {
                this.cart.push({
                    product_id: product.id,
                    brand_name: product.brand_name,
                    item_type: product.item_type,
                    unit_price: parseFloat(product.retail_price),
                    quantity: 1,
                    total_stock: product.total_stock,
                    earliest_expiry: product.earliest_expiry,
                    requires_prescription: product.requires_prescription,
                    is_controlled: product.is_controlled,
                });
            }

            this.focusBarcode();
        },

        increaseQty(index) {
            const item = this.cart[index];
            if (item.quantity < item.total_stock) {
                item.quantity++;
            }
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
            if (item.quantity > item.total_stock) {
                item.quantity = item.total_stock;
            }
            if (item.quantity < 1) {
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
            return this.cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
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
                    quantity: i.quantity,
                    unit_price: i.unit_price,
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
