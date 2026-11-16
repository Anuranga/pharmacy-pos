<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockAdjustment;
use Exception;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    /**
     * Adjust quantity of a specific product batch with mandatory reason logging.
     *
     * @param int $batchId
     * @param int $newQuantity
     * @param string $reason ('Damage', 'Expiry', 'Breakage', 'Audit Recount', 'Other')
     * @param int $adjustedBy UserId
     * @param string|null $notes
     * @return StockAdjustment
     * @throws Exception
     */
    public function adjustBatchQuantity(
        int $batchId,
        int $newQuantity,
        string $reason,
        int $adjustedBy,
        ?string $notes = null,
        ?float $newSellingPrice = null,
        ?float $newPurchaseCost = null,
        bool $updateMasterPrice = false
    ): StockAdjustment {
        $validReasons = ['Damage', 'Expiry', 'Breakage', 'Audit Recount', 'Other'];
        if (!in_array($reason, $validReasons)) {
            throw new Exception("Invalid adjustment reason. Must be one of: " . implode(', ', $validReasons));
        }

        return DB::transaction(function () use ($batchId, $newQuantity, $reason, $adjustedBy, $notes, $newSellingPrice, $newPurchaseCost, $updateMasterPrice) {
            $batch = ProductBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            $prevQty = (int) $batch->stock_quantity;
            $prevSelling = (float) $batch->selling_price;
            $prevCost = (float) $batch->purchase_cost;

            $batch->stock_quantity = $newQuantity;

            $priceChanges = [];
            if ($newPurchaseCost !== null && abs($newPurchaseCost - $prevCost) >= 0.005) {
                $batch->purchase_cost = $newPurchaseCost;
                $priceChanges[] = "Cost Rs. " . number_format($prevCost, 2) . " -> " . number_format($newPurchaseCost, 2);
            }
            if ($newSellingPrice !== null && abs($newSellingPrice - $prevSelling) >= 0.005) {
                $batch->selling_price = $newSellingPrice;
                $priceChanges[] = "Selling Rs. " . number_format($prevSelling, 2) . " -> " . number_format($newSellingPrice, 2);
            }

            $batch->save();

            // Update master product prices if requested or if this is the only batch
            $product = Product::find($batch->product_id);
            if ($product && ($updateMasterPrice || $product->batches()->count() <= 1)) {
                if ($newPurchaseCost !== null) {
                    $product->unit_cost_price = $newPurchaseCost;
                }
                if ($newSellingPrice !== null) {
                    $product->unit_retail_price = $newSellingPrice;
                }
                $product->save();
            }

            $finalNotes = $notes;
            if (!empty($priceChanges)) {
                $priceSummary = '[' . implode(', ', $priceChanges) . ']';
                $finalNotes = $finalNotes ? ($finalNotes . ' ' . $priceSummary) : $priceSummary;
            }

            $adjustment = StockAdjustment::create([
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'adjusted_by' => $adjustedBy,
                'previous_quantity' => $prevQty,
                'new_quantity' => $newQuantity,
                'reason' => $reason,
                'notes' => $finalNotes,
            ]);

            return $adjustment;
        });
    }

    /**
     * Create a new quick batch for a product when unindexed stock arrives.
     */
    public function createQuickBatch(
        int $productId,
        string $batchNumber,
        string $expiryDate,
        int $quantity,
        float $costPrice,
        float $sellingPrice,
        ?int $supplierId = null
    ): ProductBatch {
        return DB::transaction(function () use ($productId, $batchNumber, $expiryDate, $quantity, $costPrice, $sellingPrice, $supplierId) {
            $batch = ProductBatch::updateOrCreate(
                [
                    'product_id' => $productId,
                    'batch_number' => $batchNumber,
                ],
                [
                    'supplier_id' => $supplierId,
                    'expiry_date' => $expiryDate,
                    'purchase_cost' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'stock_quantity' => DB::raw("stock_quantity + {$quantity}"),
                ]
            );

            // Update product base prices
            Product::where('id', $productId)->update([
                'unit_cost_price' => $costPrice,
                'unit_retail_price' => $sellingPrice,
            ]);

            return $batch->fresh();
        });
    }
}
