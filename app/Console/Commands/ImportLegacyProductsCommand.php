<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyProductsCommand extends Command
{
    protected $signature = 'pharmacy:import-legacy';
    protected $description = 'Import 3,600+ products and batches from existing legacy pharmacy_pos_db';

    public function handle()
    {
        $this->info("Checking legacy database connection...");

        try {
            $legacyProducts = DB::connection('mysql')
                ->table('pharmacy_pos_db.products')
                ->get();

            $this->info("Found {$legacyProducts->count()} products in legacy database. Starting import...");

            $imported = 0;
            $skipped = 0;

            foreach ($legacyProducts as $lp) {
                $sku = !empty($lp->barcode) ? trim($lp->barcode) : 'LEGACY-' . str_pad($lp->id, 6, '0', STR_PAD_LEFT);

                // Category
                $categoryId = null;
                if (!empty($lp->category)) {
                    $cat = Category::firstOrCreate(['name' => trim($lp->category)]);
                    $categoryId = $cat->id;
                }

                $product = Product::firstOrCreate(
                    ['barcode_sku' => $sku],
                    [
                        'brand_name' => $lp->brand_name,
                        'generic_name' => $lp->generic_name,
                        'dosage_strength' => $lp->dosage,
                        'item_type' => strtolower($lp->base_uom ?? 'tablet'),
                        'category_id' => $categoryId,
                        'base_uom' => $lp->base_uom ?? 'Unit',
                        'units_per_pack' => $lp->units_per_pack ?? 1,
                        'reorder_min' => $lp->reorder_min_level ?? 10,
                        'reorder_max' => $lp->reorder_max_level ?? 100,
                        'requires_prescription' => (bool) ($lp->requires_prescription ?? false),
                        'is_controlled' => (bool) ($lp->is_controlled_substance ?? false),
                        'unit_cost_price' => 0.00,
                        'unit_retail_price' => 0.00,
                    ]
                );

                // Check if legacy batches exist
                $legacyBatches = DB::connection('mysql')
                    ->table('pharmacy_pos_db.batches')
                    ->where('product_id', $lp->id)
                    ->get();

                foreach ($legacyBatches as $lb) {
                    ProductBatch::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'batch_number' => $lb->batch_number,
                        ],
                        [
                            'mfg_date' => $lb->manufacture_date,
                            'expiry_date' => $lb->expiry_date,
                            'purchase_cost' => $lb->purchase_cost ?? 0,
                            'selling_price' => $lb->retail_price ?? 0,
                            'stock_quantity' => $lb->quantity_on_hand ?? 0,
                        ]
                    );

                    // Update product retail price if not set
                    if ($product->unit_retail_price == 0 && ($lb->retail_price ?? 0) > 0) {
                        $product->unit_retail_price = $lb->retail_price;
                        $product->unit_cost_price = $lb->purchase_cost;
                        $product->save();
                    }
                }

                $imported++;
            }

            $this->info("Successfully imported {$imported} products into new_pharmacy_pos_db!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Import error: " . $e->getMessage());
            return 1;
        }
    }
}
