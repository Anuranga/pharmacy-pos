<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TenantRolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    /**
     * Display the Super Admin Multi-Tenant Control Center.
     */
    public function index()
    {
        $tenants = Tenant::with(['users.role'])
            ->latest()
            ->get()
            ->map(function (Tenant $tenant) {
                $adminUser = $tenant->users->first(fn($u) => $u->role && strtolower($u->role->name) === 'admin');
                $productsCount = Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
                $salesQuery = Sale::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('status', 'completed');
                $salesCount = (clone $salesQuery)->count();
                $totalRevenue = (float) (clone $salesQuery)->sum('grand_total');

                return [
                    'tenant' => $tenant,
                    'admin_user' => $adminUser,
                    'users_count' => $tenant->users->count(),
                    'products_count' => $productsCount,
                    'sales_count' => $salesCount,
                    'total_revenue' => $totalRevenue,
                ];
            });

        $stats = [
            'total_tenants' => $tenants->count(),
            'active_tenants' => $tenants->filter(fn($t) => $t['tenant']->is_active)->count(),
            'disabled_tenants' => $tenants->filter(fn($t) => !$t['tenant']->is_active)->count(),
            'total_users' => User::whereNotNull('tenant_id')->count(),
        ];

        return view('super_admin.tenants', compact('tenants', 'stats'));
    }

    /**
     * Create a new Pharmacy Tenant and its Admin Account.
     */
    public function storeTenant(Request $request)
    {
        $validated = $request->validate([
            'pharmacy_name' => 'required|string|max:150',
            'pharmacy_phone' => 'nullable|string|max:50',
            'pharmacy_address' => 'nullable|string|max:255',
            'admin_name' => 'required|string|max:120',
            'admin_email' => 'required|email|max:150|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name' => $validated['pharmacy_name'],
                'code' => 'PHARMA-' . strtoupper(Str::random(5)),
                'phone' => $validated['pharmacy_phone'] ?? null,
                'address' => $validated['pharmacy_address'] ?? null,
                'is_active' => true,
            ]);

            TenantRolePermission::seedDefaultsForTenant($tenant->id);

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

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'phone' => $validated['pharmacy_phone'] ?? null,
                'role_id' => $adminRole->id,
                'password' => Hash::make($validated['admin_password']),
                'status' => 'active',
            ]);
        });

        return back()->with('success', "Pharmacy Tenant '{$validated['pharmacy_name']}' and Admin '{$validated['admin_email']}' created successfully.");
    }

    /**
     * Enable or Disable a Pharmacy Tenant.
     */
    public function toggleTenantStatus(Tenant $tenant)
    {
        $tenant->is_active = !$tenant->is_active;
        $tenant->save();

        $state = $tenant->is_active ? 'ENABLED' : 'DISABLED';
        return back()->with('success', "Pharmacy Tenant '{$tenant->name}' is now {$state}.");
    }

    /**
     * Update Pharmacy Tenant details and optionally reset the Tenant Admin's password.
     */
    public function updateTenant(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'admin_password' => 'nullable|string|min:6',
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        if (!empty($validated['admin_password'])) {
            $adminUser = $tenant->users()->whereHas('role', fn($q) => $q->where('name', 'admin'))->first();
            if ($adminUser) {
                $adminUser->password = Hash::make($validated['admin_password']);
                $adminUser->save();
            }
        }

        return back()->with('success', "Pharmacy Tenant '{$tenant->name}' updated successfully.");
    }
}
