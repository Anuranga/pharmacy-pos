<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerExtraPayment;
use App\Models\CustomerSettlement;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['label' => 'Administrator / Chief Pharmacist', 'description' => 'Full access to master data, stock adjustments, financial reports, and settings']
        );

        $cashierRole = Role::firstOrCreate(
            ['name' => 'cashier'],
            ['label' => 'Sales Assistant / Cashier', 'description' => 'Counter checkout, customer lookup, lending entry, and shift reconciliation']
        );

        // 2. Users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@pharmacy.com'],
            [
                'name' => 'Dr. Michael Jayawardena (Admin)',
                'phone' => '+94 77 123 4567',
                'role_id' => $adminRole->id,
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $cashierUser = User::firstOrCreate(
            ['email' => 'cashier@pharmacy.com'],
            [
                'name' => 'Nadeesha Perera (Cashier)',
                'phone' => '+94 71 987 6543',
                'role_id' => $cashierRole->id,
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        // 3. Categories
        $categoriesData = [
            ['name' => 'Antibiotics', 'code' => 'ANTI', 'description' => 'Antimicrobial and antibacterial medicines'],
            ['name' => 'Analgesics & Antipyretics', 'code' => 'ANAL', 'description' => 'Pain relievers and fever reducers'],
            ['name' => 'Gastrointestinal', 'code' => 'GAST', 'description' => 'Acid reducers, antacids, and digestive aids'],
            ['name' => 'Cardiovascular', 'code' => 'CARD', 'description' => 'Blood pressure and heart care medications'],
            ['name' => 'Antidiabetic', 'code' => 'DIAB', 'description' => 'Oral hypoglycemics and insulin formulations'],
            ['name' => 'Respiratory & Antihistamines', 'code' => 'RESP', 'description' => 'Allergy relief, cough syrups, inhalers'],
            ['name' => 'Vitamins & Supplements', 'code' => 'VITA', 'description' => 'Nutritional supplements and minerals'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['name']] = Category::firstOrCreate(['name' => $c['name']], $c);
        }

        // 4. Suppliers
        $suppliersData = [
            [
                'name' => 'State Pharmaceuticals Manufacturing (SPMC)',
                'contact_person' => 'Sunil Gunawardena',
                'phone' => '+94 11 263 5353',
                'email' => 'orders@spmc.gov.lk',
                'address' => 'No. 11, Sir John Kotelawala Mawatha, Ratmalana',
                'credit_period_days' => 45,
                'current_balance' => 125000.00,
            ],
            [
                'name' => 'George Steuart Health (Pvt) Ltd',
                'contact_person' => 'Dilshan Wickramasinghe',
                'phone' => '+94 11 470 2500',
                'email' => 'pharma@georgesteuart.lk',
                'address' => 'No. 45, Steuart House, Colombo 03',
                'credit_period_days' => 30,
                'current_balance' => 84500.00,
            ],
            [
                'name' => 'Hemas Pharmaceuticals (Pvt) Ltd',
                'contact_person' => 'Kavinda Alwis',
                'phone' => '+94 11 473 1731',
                'email' => 'distribution@hemas.com',
                'address' => 'Hemas House, No. 75, Braybrooke Place, Colombo 02',
                'credit_period_days' => 30,
                'current_balance' => 210400.00,
            ],
            [
                'name' => 'A. Baur & Co. (Healthcare Division)',
                'contact_person' => 'Rohan Senanayake',
                'phone' => '+94 11 472 8700',
                'email' => 'health@baurs.com',
                'address' => 'No. 5, Upper Chatham Street, Colombo 01',
                'credit_period_days' => 60,
                'current_balance' => 45000.00,
            ],
        ];

        $suppliers = [];
        foreach ($suppliersData as $s) {
            $suppliers[] = Supplier::firstOrCreate(['name' => $s['name']], $s);
        }

        // 5. Products Catalog
        $productsData = [
            [
                'barcode_sku' => '890123456701',
                'brand_name' => 'Panadol 500mg',
                'generic_name' => 'Paracetamol',
                'dosage_strength' => '500 mg',
                'item_type' => 'tablet',
                'category_id' => $categories['Analgesics & Antipyretics']->id,
                'base_uom' => 'Tablet',
                'units_per_pack' => 100,
                'reorder_min' => 200,
                'reorder_max' => 2000,
                'requires_prescription' => false,
                'is_controlled' => false,
                'unit_cost_price' => 3.20,
                'unit_retail_price' => 5.00,
            ],
            [
                'barcode_sku' => '890123456702',
                'brand_name' => 'Amoxil 500mg (Amoxicillin)',
                'generic_name' => 'Amoxicillin Trihydrate',
                'dosage_strength' => '500 mg',
                'item_type' => 'capsule',
                'category_id' => $categories['Antibiotics']->id,
                'base_uom' => 'Capsule',
                'units_per_pack' => 100,
                'reorder_min' => 100,
                'reorder_max' => 1000,
                'requires_prescription' => true,
                'is_controlled' => false,
                'unit_cost_price' => 18.50,
                'unit_retail_price' => 28.00,
            ],
            [
                'barcode_sku' => '890123456703',
                'brand_name' => 'Augmentin 625mg',
                'generic_name' => 'Amoxicillin + Clavulanic Acid',
                'dosage_strength' => '625 mg',
                'item_type' => 'tablet',
                'category_id' => $categories['Antibiotics']->id,
                'base_uom' => 'Tablet',
                'units_per_pack' => 14,
                'reorder_min' => 50,
                'reorder_max' => 500,
                'requires_prescription' => true,
                'is_controlled' => false,
                'unit_cost_price' => 95.00,
                'unit_retail_price' => 140.00,
            ],
            [
                'barcode_sku' => '890123456704',
                'brand_name' => 'Losec 20mg (Omeprazole)',
                'generic_name' => 'Omeprazole',
                'dosage_strength' => '20 mg',
                'item_type' => 'capsule',
                'category_id' => $categories['Gastrointestinal']->id,
                'base_uom' => 'Capsule',
                'units_per_pack' => 30,
                'reorder_min' => 100,
                'reorder_max' => 1500,
                'requires_prescription' => false,
                'is_controlled' => false,
                'unit_cost_price' => 8.50,
                'unit_retail_price' => 15.00,
            ],
            [
                'barcode_sku' => '890123456705',
                'brand_name' => 'Glucophage 500mg',
                'generic_name' => 'Metformin Hydrochloride',
                'dosage_strength' => '500 mg',
                'item_type' => 'tablet',
                'category_id' => $categories['Antidiabetic']->id,
                'base_uom' => 'Tablet',
                'units_per_pack' => 100,
                'reorder_min' => 150,
                'reorder_max' => 2000,
                'requires_prescription' => true,
                'is_controlled' => false,
                'unit_cost_price' => 4.20,
                'unit_retail_price' => 8.00,
            ],
            [
                'barcode_sku' => '890123456706',
                'brand_name' => 'Lipitor 20mg (Atorvastatin)',
                'generic_name' => 'Atorvastatin Calcium',
                'dosage_strength' => '20 mg',
                'item_type' => 'tablet',
                'category_id' => $categories['Cardiovascular']->id,
                'base_uom' => 'Tablet',
                'units_per_pack' => 30,
                'reorder_min' => 80,
                'reorder_max' => 800,
                'requires_prescription' => true,
                'is_controlled' => false,
                'unit_cost_price' => 22.00,
                'unit_retail_price' => 38.00,
            ],
            [
                'barcode_sku' => '890123456707',
                'brand_name' => 'Zyrtec 10mg (Cetirizine)',
                'generic_name' => 'Cetirizine Dihydrochloride',
                'dosage_strength' => '10 mg',
                'item_type' => 'tablet',
                'category_id' => $categories['Respiratory & Antihistamines']->id,
                'base_uom' => 'Tablet',
                'units_per_pack' => 100,
                'reorder_min' => 100,
                'reorder_max' => 1000,
                'requires_prescription' => false,
                'is_controlled' => false,
                'unit_cost_price' => 6.00,
                'unit_retail_price' => 12.00,
            ],
            [
                'barcode_sku' => '890123456708',
                'brand_name' => 'Ventolin Inhaler 100mcg',
                'generic_name' => 'Salbutamol Sulphate',
                'dosage_strength' => '100 mcg/dose',
                'item_type' => 'inhaler',
                'category_id' => $categories['Respiratory & Antihistamines']->id,
                'base_uom' => 'Canister',
                'units_per_pack' => 1,
                'reorder_min' => 20,
                'reorder_max' => 200,
                'requires_prescription' => true,
                'is_controlled' => false,
                'unit_cost_price' => 750.00,
                'unit_retail_price' => 980.00,
            ],
            [
                'barcode_sku' => '890123456709',
                'brand_name' => 'Tramal 50mg (Tramadol)',
                'generic_name' => 'Tramadol Hydrochloride',
                'dosage_strength' => '50 mg',
                'item_type' => 'capsule',
                'category_id' => $categories['Analgesics & Antipyretics']->id,
                'base_uom' => 'Capsule',
                'units_per_pack' => 20,
                'reorder_min' => 40,
                'reorder_max' => 300,
                'requires_prescription' => true,
                'is_controlled' => true, // Controlled substance!
                'unit_cost_price' => 25.00,
                'unit_retail_price' => 45.00,
            ],
            [
                'barcode_sku' => '890123456710',
                'brand_name' => 'Neurobion Forte',
                'generic_name' => 'Vitamin B1 + B6 + B12',
                'dosage_strength' => 'Forte',
                'item_type' => 'tablet',
                'category_id' => $categories['Vitamins & Supplements']->id,
                'base_uom' => 'Tablet',
                'units_per_pack' => 100,
                'reorder_min' => 100,
                'reorder_max' => 1500,
                'requires_prescription' => false,
                'is_controlled' => false,
                'unit_cost_price' => 12.00,
                'unit_retail_price' => 20.00,
            ],
        ];

        $products = [];
        foreach ($productsData as $pData) {
            $products[] = Product::firstOrCreate(['barcode_sku' => $pData['barcode_sku']], $pData);
        }

        // 6. Batches with varying expiry dates (FEFO demonstration):
        // Batch 1: Expired (for red badge & blocked from sale)
        // Batch 2: Critical <= 30 days (for orange badge)
        // Batch 3: Near Expiry <= 90 days (for yellow badge)
        // Batch 4: Good (ample shelf life)

        $batchDefs = [
            // Panadol 500mg
            [
                'product_index' => 0,
                'batch_number' => 'PAN-EXP-01',
                'expiry_date' => Carbon::now()->subDays(15)->toDateString(), // Expired
                'stock_quantity' => 40,
                'cost' => 3.00,
                'retail' => 5.00,
                'supplier_index' => 0,
            ],
            [
                'product_index' => 0,
                'batch_number' => 'PAN-CRIT-02',
                'expiry_date' => Carbon::now()->addDays(18)->toDateString(), // Critical <= 30d
                'stock_quantity' => 150,
                'cost' => 3.20,
                'retail' => 5.00,
                'supplier_index' => 0,
            ],
            [
                'product_index' => 0,
                'batch_number' => 'PAN-NEAR-03',
                'expiry_date' => Carbon::now()->addDays(75)->toDateString(), // Near <= 90d
                'stock_quantity' => 450,
                'cost' => 3.20,
                'retail' => 5.00,
                'supplier_index' => 1,
            ],
            [
                'product_index' => 0,
                'batch_number' => 'PAN-GOOD-04',
                'expiry_date' => Carbon::now()->addMonths(18)->toDateString(), // Good
                'stock_quantity' => 1200,
                'cost' => 3.30,
                'retail' => 5.00,
                'supplier_index' => 1,
            ],

            // Amoxil 500mg
            [
                'product_index' => 1,
                'batch_number' => 'AMX-CRIT-01',
                'expiry_date' => Carbon::now()->addDays(22)->toDateString(),
                'stock_quantity' => 90,
                'cost' => 18.00,
                'retail' => 28.00,
                'supplier_index' => 2,
            ],
            [
                'product_index' => 1,
                'batch_number' => 'AMX-GOOD-02',
                'expiry_date' => Carbon::now()->addMonths(14)->toDateString(),
                'stock_quantity' => 600,
                'cost' => 18.50,
                'retail' => 28.00,
                'supplier_index' => 2,
            ],

            // Augmentin 625mg
            [
                'product_index' => 2,
                'batch_number' => 'AUG-NEAR-01',
                'expiry_date' => Carbon::now()->addDays(60)->toDateString(),
                'stock_quantity' => 80,
                'cost' => 95.00,
                'retail' => 140.00,
                'supplier_index' => 1,
            ],
            [
                'product_index' => 2,
                'batch_number' => 'AUG-GOOD-02',
                'expiry_date' => Carbon::now()->addMonths(12)->toDateString(),
                'stock_quantity' => 250,
                'cost' => 95.00,
                'retail' => 140.00,
                'supplier_index' => 1,
            ],

            // Losec 20mg
            [
                'product_index' => 3,
                'batch_number' => 'LOS-EXP-01',
                'expiry_date' => Carbon::now()->subDays(5)->toDateString(), // Expired
                'stock_quantity' => 25,
                'cost' => 8.00,
                'retail' => 15.00,
                'supplier_index' => 3,
            ],
            [
                'product_index' => 3,
                'batch_number' => 'LOS-GOOD-02',
                'expiry_date' => Carbon::now()->addMonths(16)->toDateString(),
                'stock_quantity' => 800,
                'cost' => 8.50,
                'retail' => 15.00,
                'supplier_index' => 3,
            ],

            // Glucophage 500mg
            [
                'product_index' => 4,
                'batch_number' => 'GLU-GOOD-01',
                'expiry_date' => Carbon::now()->addMonths(20)->toDateString(),
                'stock_quantity' => 1500,
                'cost' => 4.20,
                'retail' => 8.00,
                'supplier_index' => 0,
            ],

            // Lipitor 20mg
            [
                'product_index' => 5,
                'batch_number' => 'LIP-CRIT-01',
                'expiry_date' => Carbon::now()->addDays(28)->toDateString(),
                'stock_quantity' => 60,
                'cost' => 22.00,
                'retail' => 38.00,
                'supplier_index' => 1,
            ],
            [
                'product_index' => 5,
                'batch_number' => 'LIP-GOOD-02',
                'expiry_date' => Carbon::now()->addMonths(15)->toDateString(),
                'stock_quantity' => 450,
                'cost' => 22.00,
                'retail' => 38.00,
                'supplier_index' => 1,
            ],

            // Zyrtec 10mg
            [
                'product_index' => 6,
                'batch_number' => 'ZYR-GOOD-01',
                'expiry_date' => Carbon::now()->addMonths(22)->toDateString(),
                'stock_quantity' => 900,
                'cost' => 6.00,
                'retail' => 12.00,
                'supplier_index' => 2,
            ],

            // Ventolin Inhaler
            [
                'product_index' => 7,
                'batch_number' => 'VEN-GOOD-01',
                'expiry_date' => Carbon::now()->addMonths(11)->toDateString(),
                'stock_quantity' => 75,
                'cost' => 750.00,
                'retail' => 980.00,
                'supplier_index' => 1,
            ],

            // Tramal 50mg (Controlled)
            [
                'product_index' => 8,
                'batch_number' => 'TRA-GOOD-01',
                'expiry_date' => Carbon::now()->addMonths(10)->toDateString(),
                'stock_quantity' => 120,
                'cost' => 25.00,
                'retail' => 45.00,
                'supplier_index' => 3,
            ],

            // Neurobion Forte
            [
                'product_index' => 9,
                'batch_number' => 'NEU-GOOD-01',
                'expiry_date' => Carbon::now()->addMonths(18)->toDateString(),
                'stock_quantity' => 1000,
                'cost' => 12.00,
                'retail' => 20.00,
                'supplier_index' => 2,
            ],
        ];

        foreach ($batchDefs as $bd) {
            $p = $products[$bd['product_index']];
            $s = $suppliers[$bd['supplier_index']];

            ProductBatch::firstOrCreate(
                [
                    'product_id' => $p->id,
                    'batch_number' => $bd['batch_number'],
                ],
                [
                    'supplier_id' => $s->id,
                    'expiry_date' => $bd['expiry_date'],
                    'purchase_cost' => $bd['cost'],
                    'selling_price' => $bd['retail'],
                    'stock_quantity' => $bd['stock_quantity'],
                ]
            );
        }

        // 7. Customers & Receivables (Lending Ledger)
        $customersData = [
            [
                'name' => 'Kamal Bandara',
                'phone' => '+94 77 345 6789',
                'nic_number' => '198234509123',
                'credit_limit' => 25000.00,
                'current_debt_balance' => 4850.00, // outstanding lending
                'address' => 'No. 24, Temple Road, Colombo 05',
            ],
            [
                'name' => 'Mrs. Anula Jayasinghe',
                'phone' => '+94 71 456 7890',
                'nic_number' => '196589012345',
                'credit_limit' => 15000.00,
                'current_debt_balance' => 2100.00,
                'address' => 'No. 88, Kandy Road, Kiribathgoda',
            ],
            [
                'name' => 'Dr. Upul Senaratne',
                'phone' => '+94 72 567 8901',
                'nic_number' => '197412345678',
                'credit_limit' => 50000.00,
                'current_debt_balance' => 12400.00,
                'address' => 'No. 102/B, Ward Place, Colombo 07',
            ],
            [
                'name' => 'Ruwan Wickramasinghe',
                'phone' => '+94 76 678 9012',
                'nic_number' => '199045678901',
                'credit_limit' => 10000.00,
                'current_debt_balance' => 0.00,
                'address' => 'No. 15, Galle Road, Dehiwala',
            ],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[] = Customer::firstOrCreate(['phone' => $c['phone']], $c);
        }

        // 8. Sample Customer Settlements & Extra Payments
        CustomerSettlement::firstOrCreate(
            ['customer_id' => $customers[0]->id, 'amount_received' => 2500.00],
            [
                'payment_method' => 'Cash',
                'notes' => 'Partial settlement for monthly diabetes medicines tab',
                'received_by' => $cashierUser->id,
                'date' => Carbon::now()->subDays(3),
            ]
        );

        CustomerExtraPayment::firstOrCreate(
            ['customer_id' => $customers[3]->id, 'amount' => 5000.00],
            [
                'payment_date' => Carbon::now()->subDays(5)->toDateString(),
                'payment_method' => 'Bank Transfer',
                'notes' => 'Advance deposit for monthly prescription delivery',
            ]
        );

        // 9. Sample Past Sales & Prescriptions
        // Sale 1: Cash sale
        $panadolBatch = ProductBatch::where('batch_number', 'PAN-CRIT-02')->first();
        if ($panadolBatch) {
            $sale1 = Sale::firstOrCreate(
                ['invoice_number' => 'INV-20261019-1001'],
                [
                    'customer_id' => null, // walk-in
                    'cashier_id' => $cashierUser->id,
                    'subtotal' => 100.00,
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'grand_total' => 100.00,
                    'payment_type' => 'cash',
                    'amount_paid' => 100.00,
                    'balance_due' => 0.00,
                    'status' => 'completed',
                    'created_at' => Carbon::now()->subHours(4),
                ]
            );

            SaleItem::firstOrCreate(
                ['sale_id' => $sale1->id, 'product_id' => $products[0]->id],
                [
                    'batch_id' => $panadolBatch->id,
                    'quantity' => 20,
                    'unit_price' => 5.00,
                    'cost_price' => 3.20,
                    'subtotal' => 100.00,
                ]
            );
        }

        // Sale 2: Credit Lending sale with Prescription
        $amoxilBatch = ProductBatch::where('batch_number', 'AMX-CRIT-01')->first();
        if ($amoxilBatch) {
            $sale2 = Sale::firstOrCreate(
                ['invoice_number' => 'INV-20261019-1002'],
                [
                    'customer_id' => $customers[0]->id,
                    'cashier_id' => $cashierUser->id,
                    'subtotal' => 560.00,
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'grand_total' => 560.00,
                    'payment_type' => 'credit_lending',
                    'amount_paid' => 0.00,
                    'balance_due' => 560.00,
                    'status' => 'completed',
                    'created_at' => Carbon::now()->subDays(1),
                ]
            );

            SaleItem::firstOrCreate(
                ['sale_id' => $sale2->id, 'product_id' => $products[1]->id],
                [
                    'batch_id' => $amoxilBatch->id,
                    'quantity' => 20,
                    'unit_price' => 28.00,
                    'cost_price' => 18.00,
                    'subtotal' => 560.00,
                ]
            );

            Prescription::firstOrCreate(
                ['sale_id' => $sale2->id],
                [
                    'doctor_name' => 'Dr. K. S. Perera',
                    'doctor_reg_no' => 'SLMC 24510',
                    'patient_name' => 'Kamal Bandara',
                    'notes' => 'Amoxil 500mg tds for 7 days post-dental procedure',
                ]
            );
        }

        // 10. Sample Stock Adjustment
        if ($panadolBatch) {
            StockAdjustment::firstOrCreate(
                ['product_id' => $products[0]->id, 'batch_id' => $panadolBatch->id],
                [
                    'adjusted_by' => $adminUser->id,
                    'previous_quantity' => 160,
                    'new_quantity' => 150,
                    'reason' => 'Damage',
                    'notes' => 'Water damage during shelf restocking',
                ]
            );
        }

        // 11. Sample GRN
        $grn1 = Grn::firstOrCreate(
            ['grn_number' => 'GRN-20261015-001'],
            [
                'supplier_id' => $suppliers[0]->id,
                'invoice_number' => 'SPMC-INV-8890',
                'invoice_date' => Carbon::now()->subDays(4)->toDateString(),
                'total_amount' => 3800.00,
                'paid_amount' => 0.00,
                'status' => 'Received',
                'received_by' => $adminUser->id,
                'notes' => 'Batch restock for Panadol and Glucophage',
            ]
        );

        GrnItem::firstOrCreate(
            ['grn_id' => $grn1->id, 'batch_number' => 'PAN-NEAR-03'],
            [
                'product_id' => $products[0]->id,
                'expiry_date' => Carbon::now()->addDays(75)->toDateString(),
                'cost_price' => 3.20,
                'retail_price' => 5.00,
                'quantity_received' => 450,
                'free_bonus_qty' => 50,
                'subtotal' => 1440.00,
            ]
        );
    }
}
