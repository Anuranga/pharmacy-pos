<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pharmacy POS & Ledger') - PharmaCare</title>

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        primary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        medical: {
                            blue: '#0284c7',
                            dark: '#0f172a',
                            accent: '#06b6d4',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Thermal Print & UI Subheading Visibility Styles -->
    @php
        $globalUiSettings = auth()->check()
            ? \App\Models\TenantRolePermission::getUiSettingsForTenant(auth()->user()->tenant_id)
            : ['show_subheadings' => false];
    @endphp
    <style>
        [x-cloak] { display: none !important; }
        @if(empty($globalUiSettings['show_subheadings']))
        .ui-subheading { display: none !important; }
        @endif
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
        }
        .print-only { display: none; }
    </style>

    @stack('styles')
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="no-print bg-slate-900 text-white shadow-lg sticky top-0 z-40 border-b border-slate-800">
        <div class="w-full max-w-[1600px] mx-auto px-3 sm:px-5">
            <div class="flex items-center justify-between h-14 gap-2">
                
                <!-- Logo & Brand -->
                <div class="flex items-center shrink-0">
                    <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.tenants.index') : route('dashboard') }}" class="flex items-center space-x-2 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-mortar-pestle text-base"></i>
                        </div>
                        <div>
                            <span class="font-display font-bold text-base tracking-tight text-white flex items-center gap-1.5 leading-none">
                                {{ auth()->user()->tenant->name ?? 'PharmaCare' }} <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded font-mono font-medium border border-emerald-500/30">{{ auth()->user()->isSuperAdmin() ? 'SUPER ADMIN' : 'POS Pro' }}</span>
                            </span>
                            <span class="text-[9px] text-slate-400 hidden xl:block tracking-wider uppercase font-medium mt-0.5">
                                {{ auth()->user()->tenant ? ('Tenant: ' . auth()->user()->tenant->code) : 'Multi-Tenant Platform Control' }}
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Main Nav Links -->
                <nav class="hidden md:flex items-center space-x-1">
                    @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('super-admin.tenants.index') }}"
                       class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('super-admin.*') ? 'bg-amber-600 text-white shadow-sm' : 'text-amber-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-crown"></i>
                        <span>Tenants Control</span>
                    </a>
                    @endif

                    @if(!auth()->user()->isSuperAdmin())
                    <!-- Executive Dashboard -->
                    @if(auth()->user()->hasPermission('view_dashboard'))
                    <a href="{{ route('dashboard') }}" 
                       class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-gauge-high {{ request()->routeIs('dashboard') ? 'text-white' : 'text-emerald-400' }}"></i>
                        <span>Dashboard</span>
                    </a>
                    @endif

                    <!-- POS Terminal -->
                    @if(auth()->user()->hasPermission('pos_access'))
                    <a href="{{ route('pos.index') }}" 
                       class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('pos.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-cash-register {{ request()->routeIs('pos.*') ? 'text-white' : 'text-emerald-400' }}"></i>
                        <span>POS Counter</span>
                        <span class="hidden 2xl:inline text-[10px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded border border-slate-700">F2/F8</span>
                    </a>
                    @endif

                    <!-- Master Data -->
                    @if(auth()->user()->hasPermission('manage_master_data'))
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('master.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>Master Data</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute left-0 mt-2 w-56 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl py-2 z-50 text-sm">
                            <a href="{{ route('master.index') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-pills mr-2 text-emerald-400"></i> Drug Catalog (Products)
                            </a>
                            <a href="{{ route('master.products.create') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-plus-circle mr-2 text-emerald-400"></i> Add New Medication
                            </a>
                            <a href="{{ route('master.categories') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-tags mr-2 text-emerald-400"></i> Therapeutic Categories
                            </a>
                            <div class="border-t border-slate-800 my-1"></div>
                            <a href="{{ route('customers.index') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-users mr-2 text-emerald-400"></i> Customer & Debtor Master
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- GRN Procurement -->
                    @if(auth()->user()->hasPermission('manage_grn'))
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('grn.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                            <span>GRN</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute left-0 mt-2 w-60 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl py-2 z-50 text-sm">
                            <a href="{{ route('grn.create') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-file-invoice mr-2 text-emerald-400"></i> New GRN Entry
                            </a>
                            <a href="{{ route('grn.index') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-list-check mr-2 text-emerald-400"></i> GRN Audit Register
                            </a>
                            <a href="{{ route('grn.item-history') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-clock-rotate-left mr-2 text-teal-400"></i> Item-Wise GRN History
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Customers & Lending -->
                    @if(auth()->user()->hasPermission('manage_customers'))
                    <a href="{{ route('customers.index') }}" 
                       class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('customers.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-users"></i>
                        <span>Customers</span>
                    </a>
                    @endif

                    <!-- Suppliers & AP -->
                    @if(auth()->user()->hasPermission('manage_suppliers'))
                    <a href="{{ route('suppliers.index') }}" 
                       class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('suppliers.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-building-wheat"></i>
                        <span>Suppliers</span>
                    </a>
                    @endif

                    <!-- Returns Hub -->
                    @if(auth()->user()->hasPermission('manage_customer_returns') || auth()->user()->hasPermission('manage_supplier_returns'))
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('returns.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-rotate-left text-amber-400"></i>
                            <span>Returns</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute left-0 mt-2 w-64 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl py-2 z-50 text-sm">
                            @if(auth()->user()->hasPermission('manage_customer_returns'))
                            <a href="{{ route('returns.customer.create') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-receipt mr-2 text-emerald-400"></i> Customer Bill Return
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('manage_supplier_returns'))
                            <a href="{{ route('returns.supplier.create') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-truck-ramp-box mr-2 text-indigo-400"></i> Shop GRN Return (Supplier)
                            </a>
                            @endif
                            <div class="border-t border-slate-800 my-1"></div>
                            <a href="{{ route('returns.index') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-medium">
                                <i class="fa-solid fa-list-check mr-2 text-amber-400"></i> Returns Audit Register
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Reports Hub -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('reports.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-chart-line text-emerald-400"></i>
                            <span>Reports</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute right-0 mt-2 w-72 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl py-2 z-50 text-sm max-h-[85vh] overflow-y-auto divide-y divide-slate-800">
                            
                            <!-- Clinical & POS Reports -->
                            <div class="py-1">
                                <div class="px-4 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Sales & Cashier</div>
                                @if(auth()->user()->hasPermission('view_eod_report'))
                                <a href="{{ route('reports.eod-z-report') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-calculator w-5 text-emerald-400"></i> 11. EOD Z-Report (Register Close)
                                </a>
                                @endif
                                @if(auth()->user()->hasPermission('view_management_reports'))
                                <a href="{{ route('reports.sales-by-customer') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-trophy w-5 text-amber-400"></i> 5. Sales by Customer
                                </a>
                                <a href="{{ route('reports.patient-rx-history') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-file-prescription w-5 text-cyan-400"></i> 7. Patient Rx & Controlled History
                                </a>
                                @endif
                            </div>

                            <!-- Customers & Lending -->
                            @if(auth()->user()->hasPermission('view_management_reports'))
                            <div class="py-1">
                                <div class="px-4 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Receivables & Lending</div>
                                <a href="{{ route('reports.customer-vs-transaction') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-receipt w-5 text-teal-400"></i> 1. Customer vs Transaction
                                </a>
                                <a href="{{ route('reports.customer-aging') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-business-time w-5 text-rose-400"></i> 3. Customer A/R Aging (0-90+d)
                                </a>
                                <a href="{{ route('reports.customer-balance-summary') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-scale-balanced w-5 text-blue-400"></i> 4. Customer Balance Summary
                                </a>
                                <a href="{{ route('reports.customer-extra-payments') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-wallet w-5 text-emerald-400"></i> 6. Customer Extra Payments
                                </a>
                            </div>

                            <!-- Inventory & Suppliers -->
                            <div class="py-1">
                                <div class="px-4 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Inventory & Financials</div>
                                <a href="{{ route('reports.item-vs-transaction') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-semibold">
                                    <i class="fa-solid fa-capsules w-5 text-emerald-400"></i> 13. Item vs Transaction (Sold & GRN)
                                </a>
                                <a href="{{ route('reports.item-stock-movement') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400 font-semibold">
                                    <i class="fa-solid fa-arrows-left-right-to-line w-5 text-teal-400"></i> 12. Item Stock Movement (PDF/Excel)
                                </a>
                                <a href="{{ route('reports.expiry-alerts') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-triangle-exclamation w-5 text-amber-500"></i> 9. FEFO Expiry Alerts
                                </a>
                                <a href="{{ route('reports.inventory-valuation') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-boxes-packing w-5 text-indigo-400"></i> 8. Inventory Valuation
                                </a>
                                <a href="{{ route('reports.supplier-vs-transaction') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-truck-moving w-5 text-sky-400"></i> 2. Supplier vs Transaction
                                </a>
                                <a href="{{ route('reports.profit-loss') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-chart-pie w-5 text-emerald-400"></i> 10. Profit & Loss Statement
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Pharmacy Admin: Staff & Role Permissions -->
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('staff.index') }}"
                       class="px-2.5 py-1.5 rounded-lg text-xs xl:text-sm font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request()->routeIs('staff.*') ? 'bg-purple-600 text-white shadow-sm' : 'text-purple-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Staff & Permissions</span>
                    </a>
                    @endif
                    @endif
                </nav>

                <!-- User, Role Switcher & Prominent Logout -->
                <div class="flex items-center space-x-2 shrink-0">
                    <!-- User Profile & Shift -->
                    <div class="flex items-center space-x-2 bg-slate-800/80 px-2.5 py-1 rounded-xl border border-slate-700/80">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-[11px] {{ auth()->user()->isAdmin() ? 'bg-purple-600 text-white' : 'bg-emerald-600 text-white' }}">
                            {{ auth()->user()->isAdmin() ? 'ADM' : 'CSH' }}
                        </div>
                        <div class="text-left hidden xl:block">
                            <div class="text-xs font-semibold text-white leading-tight truncate max-w-[110px]">{{ auth()->user()->name }}</div>
                            <div class="text-[9px] text-emerald-400 uppercase tracking-wider font-mono font-medium">{{ auth()->user()->role->label }}</div>
                        </div>

                        <!-- 1-Click Role Switcher Dropdown (Scoped to Active Tenant) -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" title="Switch Role / Staff Account"
                                    class="text-slate-400 hover:text-white p-1 rounded transition-colors text-xs">
                                <i class="fa-solid fa-repeat"></i>
                            </button>
                            <div x-show="open" x-cloak
                                 class="absolute right-0 mt-2 w-64 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl p-2 z-50 text-xs">
                                <div class="px-2 py-1 font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Switch Shop User</div>
                                @php
                                    $switchUsers = auth()->user()->isSuperAdmin()
                                        ? \App\Models\User::with('role')->whereNotNull('tenant_id')->limit(15)->get()
                                        : \App\Models\User::with('role')->where('tenant_id', auth()->user()->tenant_id)->get();
                                @endphp
                                @foreach($switchUsers as $u)
                                <a href="{{ route('auth.switch', $u->id) }}" 
                                   class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-800 text-slate-200 transition-colors {{ auth()->id() == $u->id ? 'bg-slate-800/80 text-emerald-400 font-semibold' : '' }}">
                                    <span>{{ $u->name }}</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded {{ $u->isAdmin() ? 'bg-purple-900/50 text-purple-300' : 'bg-emerald-900/50 text-emerald-300' }}">{{ $u->role->name ?? 'staff' }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>

                        <!-- Prominent Logout Button -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Logout" class="px-2 py-1 rounded-lg bg-rose-500/15 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 transition-all text-xs font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-power-off text-[11px]"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- Global Alert Messages -->
    @if(session('success') || session('error') || (isset($errors) && $errors->any()))
    <div class="no-print max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
        @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 text-sm text-emerald-800 flex items-center justify-between shadow-sm" x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
        @endif

        @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-sm text-rose-800 flex items-center justify-between shadow-sm" x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-600 hover:text-rose-900">&times;</button>
        </div>
        @endif

        @if($errors->any())
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3.5 text-sm text-amber-800 shadow-sm" x-data="{ show: true }" x-show="show">
            <div class="font-semibold flex items-center gap-2 mb-1">
                <i class="fa-solid fa-circle-exclamation text-amber-600"></i> Please resolve the following errors:
            </div>
            <ul class="list-disc list-inside text-xs space-y-0.5 text-amber-700">
                @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    <!-- Main Content Area -->
    <main class="flex-1 overflow-x-hidden">
        @yield('content')
    </main>

    <!-- Global Confirmation & Notification Helpers -->
    <script>
        function confirmDeleteForm(event, itemName, itemType = 'Record') {
            event.preventDefault();
            const form = event.target;
            Swal.fire({
                title: `Delete ${itemType}?`,
                html: `Are you sure you want to delete <strong>${itemName}</strong>?<br><span class="text-xs text-slate-500 mt-1 block">This action will remove the ${itemType.toLowerCase()} from your active records.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-trash-can mr-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        function confirmBalanceAdjustment(event, entityName, entityType = 'Account') {
            event.preventDefault();
            const form = event.target;
            const modeSelect = form.querySelector('[name="adjustment_mode"]');
            const amountInput = form.querySelector('[name="amount"]');
            const modeText = modeSelect ? modeSelect.options[modeSelect.selectedIndex].text : 'Adjust Balance';
            const amountVal = amountInput ? parseFloat(amountInput.value || 0).toFixed(2) : '0.00';

            Swal.fire({
                title: `Confirm ${entityType} Balance Adjustment?`,
                html: `<div class="text-left text-xs bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-1 mt-2">
                        <div><span class="text-slate-500">${entityType}:</span> <strong class="text-slate-900">${entityName}</strong></div>
                        <div><span class="text-slate-500">Adjustment Mode:</span> <strong class="text-indigo-700">${modeText}</strong></div>
                        <div><span class="text-slate-500">Adjustment Amount:</span> <strong class="font-mono text-emerald-700">Rs. ${amountVal}</strong></div>
                       </div>
                       <p class="text-xs text-slate-500 mt-2">Click <strong>Confirm & Proceed</strong> to save this balance change to the ledger.</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-check-circle mr-1"></i> Confirm & Proceed',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: @json(session('success')),
                timer: 3200,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true
            });
            @endif

            @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Action Blocked / Error',
                text: @json(session('error')),
                confirmButtonColor: '#e11d48'
            });
            @endif
        });
    </script>

    @stack('scripts')
</body>
</html>
