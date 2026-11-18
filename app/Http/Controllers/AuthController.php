<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantRolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isSuperAdmin()
                ? redirect()->route('super-admin.tenants.index')
                : redirect()->route('dashboard');
        }

        $users = User::with(['role', 'tenant'])->where('status', 'active')->get();
        return view('auth.login', compact('users'));
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($validated['email']);
        $passwordInput = $validated['password'];

        // 1. Hardcoded Super Admin Authentication Support
        if (in_array(strtolower($loginInput), ['superadmin', 'superadmin@pharmacy.com'], true)
            && in_array($passwordInput, ['superadmin123', 'password'], true)) {

            $superRole = Role::firstOrCreate(
                ['name' => 'super_admin'],
                ['label' => 'Super Administrator', 'description' => 'Platform-wide multi-tenant controller']
            );

            $superUser = User::firstOrCreate(
                ['email' => 'superadmin@pharmacy.com'],
                [
                    'tenant_id' => null,
                    'name' => 'System Super Admin',
                    'phone' => '+94 77 000 0000',
                    'role_id' => $superRole->id,
                    'password' => Hash::make('superadmin123'),
                    'status' => 'active',
                ]
            );

            Auth::login($superUser, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('super-admin.tenants.index')
                ->with('success', 'Logged in as System Super Administrator.');
        }

        // 2. Standard Tenant User Login (Admin, Manager, Cashier, Staff)
        $user = User::with(['role', 'tenant'])->where('email', $loginInput)->first();

        if (!$user || !Hash::check($passwordInput, $user->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'email' => 'Your staff account has been disabled by your Pharmacy Administrator.',
            ])->onlyInput('email');
        }

        if (!$user->isSuperAdmin() && $user->tenant && !$user->tenant->is_active) {
            return back()->withErrors([
                'email' => "Access Denied: Pharmacy tenant '{$user->tenant->name}' is currently disabled by the Super Administrator.",
            ])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.tenants.index');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Show Pharmacy Tenant + Admin Registration Form.
     */
    public function showRegisterPharmacy()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register_pharmacy');
    }

    /**
     * Register a new Pharmacy Tenant and its Owner Admin Account.
     */
    public function registerPharmacy(Request $request)
    {
        $validated = $request->validate([
            'pharmacy_name' => 'required|string|max:150',
            'pharmacy_phone' => 'nullable|string|max:50',
            'pharmacy_address' => 'nullable|string|max:255',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name' => $validated['pharmacy_name'],
                'code' => 'PHARMA-' . strtoupper(Str::random(5)),
                'phone' => $validated['pharmacy_phone'] ?? null,
                'address' => $validated['pharmacy_address'] ?? null,
                'is_active' => true,
            ]);

            TenantRolePermission::seedDefaultsForTenant($tenant->id);

            // Seed default therapeutic categories for this new pharmacy tenant
            $defaultCategories = [
                ['name' => 'Antibiotics', 'code' => 'ANT', 'description' => 'Antibacterial medications'],
                ['name' => 'Analgesics & Antipyretics', 'code' => 'ANA', 'description' => 'Pain relievers and fever reducers'],
                ['name' => 'Cardiovascular', 'code' => 'CRD', 'description' => 'Heart and blood pressure medications'],
                ['name' => 'Antidiabetic', 'code' => 'DIA', 'description' => 'Diabetes management medications'],
                ['name' => 'Gastrointestinal', 'code' => 'GAS', 'description' => 'Digestive system medications'],
                ['name' => 'General / OTC', 'code' => 'OTC', 'description' => 'Over-the-counter & wellness items'],
            ];

            foreach ($defaultCategories as $cat) {
                Category::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $cat['name'],
                    'code' => $cat['code'],
                    'description' => $cat['description'],
                ]);
            }

            $adminRole = Role::firstOrCreate(
                ['name' => 'admin'],
                ['label' => 'Pharmacy Admin', 'description' => 'Full pharmacy tenant owner and administrator']
            );

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['pharmacy_phone'] ?? null,
                'role_id' => $adminRole->id,
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', "Welcome! Pharmacy '{$user->tenant->name}' and your Admin account have been created.");
    }

    /**
     * Quick switch between demo roles (scoped to the current tenant, or Super Admin).
     */
    public function switchUser(User $user)
    {
        $current = Auth::user();
        if ($current && !$current->isSuperAdmin() && $user->tenant_id !== $current->tenant_id && !$user->isSuperAdmin()) {
            abort(403, 'Cannot switch to a user from another pharmacy tenant.');
        }

        if (!$user->isSuperAdmin() && $user->tenant && !$user->tenant->is_active) {
            return back()->with('error', "Cannot switch: Tenant '{$user->tenant->name}' is disabled.");
        }

        Auth::login($user);

        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.tenants.index')
                ->with('success', "Switched to System Super Administrator.");
        }

        return redirect()->route('dashboard')
            ->with('success', "Logged in as {$user->name} ({$user->role->label} - {$user->tenant?->name})");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
