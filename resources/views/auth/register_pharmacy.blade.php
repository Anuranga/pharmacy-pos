<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Pharmacy Shop - PharmaCare Multi-Tenant POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-full flex items-center justify-center p-4 py-8 selection:bg-emerald-500 selection:text-white font-sans">

    <div class="max-w-lg w-full">
        
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 items-center justify-center text-white shadow-xl shadow-emerald-500/20 mb-2">
                <i class="fa-solid fa-store text-2xl"></i>
            </div>
            <h1 class="font-display font-extrabold text-2xl text-white tracking-tight">Register Pharmacy Shop (Tenant)</h1>
            <p class="text-slate-400 text-xs mt-1">Create your isolated Pharmacy Tenant & Owner Admin Account</p>
        </div>

        <div class="bg-slate-800/80 backdrop-blur-md rounded-3xl p-6 sm:p-8 border border-slate-700 shadow-2xl">

            @if($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                @foreach($errors->all() as $err)
                <div>&bull; {{ $err }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('register.pharmacy.submit') }}" class="space-y-4 text-xs">
                @csrf

                <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-700/80 space-y-3">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-mortar-pestle"></i> 1. Pharmacy Shop (Tenant) Details
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 uppercase tracking-wider mb-1">Pharmacy / Shop Name *</label>
                        <input type="text" name="pharmacy_name" required value="{{ old('pharmacy_name') }}" placeholder="e.g. City Care Pharmacy"
                               class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase tracking-wider mb-1">Shop Phone</label>
                            <input type="text" name="pharmacy_phone" value="{{ old('pharmacy_phone') }}" placeholder="+94 7X XXX XXXX"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase tracking-wider mb-1">Shop Address / City</label>
                            <input type="text" name="pharmacy_address" value="{{ old('pharmacy_address') }}" placeholder="e.g. Kandy Road, Colombo"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-700/80 space-y-3">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-teal-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-user-shield"></i> 2. Pharmacy Admin Account Credentials
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase tracking-wider mb-1">Admin Full Name *</label>
                            <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Dr. Kasun Perera"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase tracking-wider mb-1">Admin Email (Login ID) *</label>
                            <input type="email" name="email" required value="{{ old('email') }}" placeholder="owner@citypharmacy.com"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase tracking-wider mb-1">Password *</label>
                            <input type="password" name="password" required minlength="6" placeholder="Min 6 characters"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase tracking-wider mb-1">Confirm Password *</label>
                            <input type="password" name="password_confirmation" required minlength="6" placeholder="Re-enter password"
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-sm tracking-wide shadow-lg shadow-emerald-900/40 transition-all cursor-pointer">
                    CREATE PHARMACY TENANT & ADMIN ACCOUNT
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-slate-700/80 text-center">
                <a href="{{ route('login') }}" class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">
                    &larr; Already have an account? Sign In
                </a>
            </div>
        </div>

    </div>

</body>
</html>
