<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantRolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'role_name',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Logical grouped catalog of all configurable shop permissions.
     */
    public static function permissionGroups(): array
    {
        return [
            'POS & Counter Billing' => [
                'pos_access' => 'Access POS Counter & Process Customer Bills',
                'pos_price_discount' => 'Adjust Item Prices & Apply Bill Discounts at POS',
                'pos_quick_create' => 'Quick-Register New Medications from POS Counter',
                'pos_void_sale' => 'Void Completed POS Invoices',
            ],
            'Inventory & Master Catalog' => [
                'manage_master_data' => 'View, Add & Edit Medications and Therapeutic Categories',
                'adjust_stock_price' => 'Adjust Batch Physical Stock Counts & Batch Selling Prices',
            ],
            'Procurement & Suppliers (GRN)' => [
                'manage_grn' => 'Create & View Goods Received Notes (GRN) & Item History',
                'manage_suppliers' => 'Manage Suppliers & Record Accounts Payable Payouts',
            ],
            'Customers & Lending Ledger' => [
                'manage_customers' => 'Manage Customers, Credit Limits & Debt Settlements',
            ],
            'Returns Management' => [
                'manage_customer_returns' => 'Process Customer Billed Item Returns (Sales Returns)',
                'manage_supplier_returns' => 'Process Shop GRN Returns to Suppliers (Debit Notes)',
            ],
            'Dashboard & Reports' => [
                'view_dashboard' => 'View Executive & Clinical Operations Dashboard',
                'view_eod_report' => 'View EOD Z-Report (Cashier Register Closure)',
                'view_management_reports' => 'View Full Financial, Valuation, Expiry & Stock Movement Reports (PDF/Excel)',
            ],
        ];
    }

    /**
     * Default permissions per role when a new Pharmacy Tenant is created.
     */
    public static function defaultPermissionsForRole(string $roleName): array
    {
        $role = strtolower($roleName);

        if ($role === 'manager') {
            return [
                'pos_access',
                'pos_price_discount',
                'pos_quick_create',
                'manage_master_data',
                'adjust_stock_price',
                'manage_grn',
                'manage_suppliers',
                'manage_customers',
                'manage_customer_returns',
                'manage_supplier_returns',
                'view_dashboard',
                'view_eod_report',
                'view_management_reports',
            ];
        }

        if ($role === 'cashier') {
            return [
                'pos_access',
                'pos_price_discount',
                'manage_customers',
                'manage_customer_returns',
                'view_dashboard',
                'view_eod_report',
            ];
        }

        // staff
        return [
            'pos_access',
            'manage_master_data',
            'manage_grn',
            'view_dashboard',
        ];
    }

    /**
     * Seed default role permission rows for a given tenant.
     */
    public static function seedDefaultsForTenant(int $tenantId): void
    {
        foreach (['manager', 'cashier', 'staff'] as $roleName) {
            static::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'role_name' => $roleName,
                ],
                [
                    'permissions' => static::defaultPermissionsForRole($roleName),
                ]
            );
        }
    }
}
