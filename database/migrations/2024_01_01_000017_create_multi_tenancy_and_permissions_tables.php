<?php

use App\Models\TenantRolePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tenantTables = [
        'users',
        'categories',
        'products',
        'product_batches',
        'customers',
        'customer_settlements',
        'customer_extra_payments',
        'suppliers',
        'supplier_payments',
        'grns',
        'sales',
        'prescriptions',
        'stock_adjustments',
        'sale_returns',
        'purchase_returns',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 50)->unique();
                $table->string('phone', 50)->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_role_permissions')) {
            Schema::create('tenant_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('role_name', 40);
                $table->json('permissions')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'role_name']);
            });
        }

        // Add tenant_id to all tenant-scoped tables
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                });
            }
        }

        // Replace global unique on categories(name) and products(barcode_sku) with per-tenant unique
        try {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropUnique(['name']);
                $table->unique(['tenant_id', 'name']);
            });
        } catch (\Throwable $e) {
            // Ignore if already adjusted
        }

        try {
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique(['barcode_sku']);
                $table->unique(['tenant_id', 'barcode_sku']);
            });
        } catch (\Throwable $e) {
            // Ignore if already adjusted
        }

        // Ensure all 5 roles exist
        $rolesData = [
            ['name' => 'super_admin', 'label' => 'Super Administrator', 'description' => 'Platform-wide multi-tenant controller'],
            ['name' => 'admin', 'label' => 'Pharmacy Admin', 'description' => 'Full pharmacy tenant owner and administrator'],
            ['name' => 'manager', 'label' => 'Pharmacy Manager', 'description' => 'Store manager with configurable permissions'],
            ['name' => 'cashier', 'label' => 'POS Cashier', 'description' => 'Counter billing and customer ledger operator'],
            ['name' => 'staff', 'label' => 'Pharmacy Staff', 'description' => 'Inventory and counter support staff'],
        ];

        foreach ($rolesData as $r) {
            DB::table('roles')->updateOrInsert(
                ['name' => $r['name']],
                [
                    'label' => $r['label'],
                    'description' => $r['description'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Create default Tenant #1 for existing shop data
        $defaultTenantId = DB::table('tenants')->where('code', 'PHARMA-001')->value('id');
        if (!$defaultTenantId) {
            $defaultTenantId = DB::table('tenants')->insertGetId([
                'name' => 'PharmaCare Main Pharmacy',
                'code' => 'PHARMA-001',
                'phone' => '+94 11 234 5678',
                'address' => 'Colombo, Sri Lanka',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign all existing records to Default Tenant #1
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            }
        }

        // Seed default role permissions for Tenant #1
        TenantRolePermission::seedDefaultsForTenant((int) $defaultTenantId);

        // Ensure Super Admin user exists (with tenant_id = null so they manage all tenants)
        $superAdminRoleId = DB::table('roles')->where('name', 'super_admin')->value('id');
        if ($superAdminRoleId) {
            $existingSuper = DB::table('users')->where('email', 'superadmin@pharmacy.com')->first();
            if (!$existingSuper) {
                DB::table('users')->insert([
                    'tenant_id' => null,
                    'name' => 'System Super Admin',
                    'email' => 'superadmin@pharmacy.com',
                    'phone' => '+94 77 000 0000',
                    'role_id' => $superAdminRoleId,
                    'password' => Hash::make('superadmin123'),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('users')->where('id', $existingSuper->id)->update([
                    'tenant_id' => null,
                    'role_id' => $superAdminRoleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tenant_id');
                });
            }
        }

        Schema::dropIfExists('tenant_role_permissions');
        Schema::dropIfExists('tenants');
    }
};
