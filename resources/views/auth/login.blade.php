<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PharmaCare POS Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="h-full flex items-center justify-center p-4 selection:bg-emerald-500 selection:text-white font-sans">

    <div class="max-w-md w-full">
        
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 items-center justify-center text-white shadow-xl shadow-emerald-500/20 mb-3">
                <i class="fa-solid fa-mortar-pestle text-3xl"></i>
            </div>
            <h1 class="font-display font-extrabold text-3xl text-white tracking-tight">PharmaCare POS</h1>
            <p class="text-slate-400 text-sm mt-1">Pharmacy POS, Inventory & Financial Ledger</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-800/80 backdrop-blur-md rounded-3xl p-8 border border-slate-700 shadow-2xl">
            
            <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                <i class="fa-solid fa-lock text-emerald-400"></i> Register Sign In
            </h2>

            @if($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </div>
                        <input type="email" name="email" id="email" required value="{{ old('email', 'admin@pharmacy.com') }}"
                               class="w-full pl-10 pr-3 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-key text-sm"></i>
                        </div>
                        <input type="password" name="password" id="password" required value="password"
                               class="w-full pl-10 pr-3 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-medium">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-500">
                        <span>Keep me signed in</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-sm tracking-wide shadow-lg shadow-emerald-900/40 transition-all cursor-pointer">
                    SIGN IN TO REGISTER
                </button>
            </form>

            <!-- Quick Demo Role Switcher -->
            <div class="mt-8 pt-6 border-t border-slate-700/80">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider text-center mb-3">1-Click Fast Login (Demo Accounts)</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" 
                            onclick="document.getElementById('email').value='admin@pharmacy.com'; document.getElementById('password').value='password'; document.forms[0].submit();"
                            class="p-2.5 rounded-xl bg-purple-950/60 hover:bg-purple-900/80 border border-purple-800 text-purple-200 font-semibold text-center transition-colors">
                        <i class="fa-solid fa-user-shield text-purple-400 mb-1 block"></i>
                        Chief Pharmacist (Admin)
                    </button>

                    <button type="button" 
                            onclick="document.getElementById('email').value='cashier@pharmacy.com'; document.getElementById('password').value='password'; document.forms[0].submit();"
                            class="p-2.5 rounded-xl bg-emerald-950/60 hover:bg-emerald-900/80 border border-emerald-800 text-emerald-200 font-semibold text-center transition-colors">
                        <i class="fa-solid fa-cash-register text-emerald-400 mb-1 block"></i>
                        Senior Cashier (POS)
                    </button>
                </div>
            </div>

        </div>

        <div class="text-center mt-6 text-xs text-slate-500">
            PharmaCare POS Pro &bull; Production-Grade Laravel &bull; XAMPP Local Deployment
        </div>

    </div>

</body>
</html>
