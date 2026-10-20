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

    <!-- Thermal Print Styles -->
    <style>
        [x-cloak] { display: none !important; }
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('pos.index') }}" class="flex items-center space-x-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-mortar-pestle text-lg"></i>
                        </div>
                        <div>
                            <span class="font-display font-bold text-lg tracking-tight text-white flex items-center gap-1.5">
                                PharmaCare <span class="text-xs bg-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded font-mono font-medium border border-emerald-500/30">POS Pro</span>
                            </span>
                            <span class="text-[10px] text-slate-400 block tracking-wider uppercase font-medium">Pharmacy & Financial Ledger</span>
                        </div>
                    </a>
                </div>

                <!-- Main Nav Links -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <!-- POS Terminal -->
                    <a href="{{ route('pos.index') }}" 
                       class="px-3 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2 {{ request()->routeIs('pos.*') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-cash-register text-emerald-400"></i>
                        <span>POS Counter</span>
                        <span class="text-[10px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded border border-slate-700">F2/F8</span>
                    </a>

                    <!-- Master Data (Admin Only) -->
                    @if(auth()->user()->isAdmin())
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="px-3 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-1.5 {{ request()->routeIs('master.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>Master Data</span>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
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

                    <!-- GRN Procurement (Admin Only) -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="px-3 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-1.5 {{ request()->routeIs('grn.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                            <span>GRN / Procurement</span>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
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

                    <!-- Customers & Lending (Cashier & Admin) -->
                    <a href="{{ route('customers.index') }}" 
                       class="px-3 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-1.5 {{ request()->routeIs('customers.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-users"></i>
                        <span>Customers & Lending</span>
                    </a>

                    <!-- Suppliers & AP (Admin Only) -->
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('suppliers.index') }}" 
                       class="px-3 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-1.5 {{ request()->routeIs('suppliers.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                        <i class="fa-solid fa-building-wheat"></i>
                        <span>Suppliers & AP</span>
                    </a>
                    @endif

                    <!-- Reports Hub (Exact 11 Reports) -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" 
                                class="px-3 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-1.5 {{ request()->routeIs('reports.*') ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <i class="fa-solid fa-chart-line text-emerald-400"></i>
                            <span>Reports</span>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute right-0 mt-2 w-72 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl py-2 z-50 text-sm max-h-[85vh] overflow-y-auto divide-y divide-slate-800">
                            
                            <!-- Clinical & POS Reports -->
                            <div class="py-1">
                                <div class="px-4 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Sales & Cashier</div>
                                <a href="{{ route('reports.eod-z-report') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-calculator w-5 text-emerald-400"></i> 11. EOD Z-Report (Register Close)
                                </a>
                                <a href="{{ route('reports.sales-by-customer') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-trophy w-5 text-amber-400"></i> 5. Sales by Customer
                                </a>
                                <a href="{{ route('reports.patient-rx-history') }}" class="block px-4 py-2 text-slate-200 hover:bg-slate-800 hover:text-emerald-400">
                                    <i class="fa-solid fa-file-prescription w-5 text-cyan-400"></i> 7. Patient Rx & Controlled History
                                </a>
                            </div>

                            <!-- Customers & Lending -->
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
                            @if(auth()->user()->isAdmin())
                            <div class="py-1">
                                <div class="px-4 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Inventory & Financials</div>
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
                </nav>

                <!-- User & Quick Role Switcher -->
                <div class="flex items-center space-x-3">
                    <!-- User Profile & Shift -->
                    <div class="flex items-center space-x-3 bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700/80">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs {{ auth()->user()->isAdmin() ? 'bg-purple-600 text-white' : 'bg-emerald-600 text-white' }}">
                            {{ auth()->user()->isAdmin() ? 'ADM' : 'CSH' }}
                        </div>
                        <div class="text-left hidden lg:block">
                            <div class="text-xs font-semibold text-white leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-emerald-400 uppercase tracking-wider font-mono font-medium">{{ auth()->user()->role->label }}</div>
                        </div>

                        <!-- 1-Click Role Switcher Dropdown (Local XAMPP Testing) -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" title="Switch Role / Cashier"
                                    class="text-slate-400 hover:text-white p-1 rounded transition-colors text-xs">
                                <i class="fa-solid fa-repeat"></i>
                            </button>
                            <div x-show="open" x-cloak
                                 class="absolute right-0 mt-2 w-64 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl p-2 z-50 text-xs">
                                <div class="px-2 py-1 font-semibold text-slate-400 uppercase tracking-wider text-[10px]">Switch Active Session</div>
                                @foreach(\App\Models\User::with('role')->get() as $u)
                                <a href="{{ route('auth.switch', $u->id) }}" 
                                   class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-800 text-slate-200 transition-colors {{ auth()->id() == $u->id ? 'bg-slate-800/80 text-emerald-400 font-semibold' : '' }}">
                                    <span>{{ $u->name }}</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded {{ $u->isAdmin() ? 'bg-purple-900/50 text-purple-300' : 'bg-emerald-900/50 text-emerald-300' }}">{{ $u->role->name }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Logout" class="text-slate-400 hover:text-rose-400 p-1 rounded transition-colors text-xs">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- Global Alert Messages -->
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

    <!-- Main Content Area -->
    <main class="flex-1 overflow-x-hidden">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
