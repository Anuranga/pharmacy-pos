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

    /**
     * Configurable UI & Dashboard Display Settings (Admin Enable/Disable Controls).
     * Note: 'show_subheadings' is disabled (false) by default as requested by client.
     */
    public static function uiSettingDefinitions(): array
    {
        return [
            'show_subheadings' => [
                'label' => 'Sub-Heading Descriptions (Under Page & Section Titles)',
                'description' => 'Show descriptive subtitle text such as "Real-time pharmacy sales, FEFO batch expiry radar...", "Latest POS transactions with instant thermal receipt reprint", "Highest outstanding debtor balances & credit limit usage", etc. (Disabled by default for a cleaner interface)',
                'default' => false,
            ],
            'show_kpi_cards' => [
                'label' => 'Dashboard: Top 6 Financial & Stock KPI Cards',
                'description' => 'Display Today\'s Revenue, Cash in Drawer, Month Revenue, Customer Debt, Supplier Payable, and Stock Valuation summary cards.',
                'default' => true,
            ],
            'show_revenue_chart' => [
                'label' => 'Dashboard: Last 7 Days Revenue & Billing Trend Chart',
                'description' => 'Display daily completed counter sales volume and invoice count bar chart.',
                'default' => true,
            ],
            'show_tender_breakdown' => [
                'label' => 'Dashboard: Today\'s Tender Breakdown & FEFO Radar',
                'description' => 'Display live split across payment channels (Cash, Card, LankaQR, Credit) and batch shelf-life status.',
                'default' => true,
            ],
            'show_action_center' => [
                'label' => 'Dashboard: Pharmacist Operational Action Center',
                'description' => 'Display tabbed alerts for Negative & Low Stock, FEFO Expiry Watch, and Top Selling Drugs.',
                'default' => true,
            ],
            'show_recent_invoices' => [
                'label' => 'Dashboard: Recent Counter Invoices (Thermal Receipt Reprint)',
                'description' => 'Display latest POS transactions table with instant thermal receipt reprint.',
                'default' => true,
            ],
            'show_debtor_watchlist' => [
                'label' => 'Dashboard: Customer Lending Watchlist (Top Debtors)',
                'description' => 'Display highest outstanding debtor balances and credit limit usage.',
                'default' => true,
            ],
        ];
    }

    /**
     * Retrieve boolean map of UI & Dashboard display settings for a tenant.
     */
    public static function getUiSettingsForTenant(?int $tenantId): array
    {
        $definitions = static::uiSettingDefinitions();
        $defaults = [];
        foreach ($definitions as $key => $meta) {
            $defaults[$key] = (bool) ($meta['default'] ?? false);
        }

        if (!$tenantId) {
            return $defaults;
        }

        try {
            $row = static::where('tenant_id', $tenantId)
                ->where('role_name', 'ui_settings')
                ->first();

            if (!$row) {
                return $defaults;
            }

            $enabledKeys = (array) ($row->permissions ?? []);
            $resolved = [];
            foreach ($definitions as $key => $meta) {
                $resolved[$key] = in_array($key, $enabledKeys, true);
            }

            return $resolved;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    /**
     * Save UI & Dashboard display settings for a tenant.
     */
    public static function saveUiSettingsForTenant(int $tenantId, array $enabledKeys): void
    {
        $validKeys = array_keys(static::uiSettingDefinitions());
        $cleanKeys = array_values(array_intersect($validKeys, $enabledKeys));

        static::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'role_name' => 'ui_settings',
            ],
            [
                'permissions' => $cleanKeys,
            ]
        );
    }
}
