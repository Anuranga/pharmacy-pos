<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use Exception;
use Illuminate\Support\Facades\DB;

class FEFOAllocationService
{
    /**
     * Allocate quantity across unexpired batches using FEFO (First-Expired, First-Out).
     * Strictly enforces pessimistic row locking (lockForUpdate) during deduction.
     * Allows stock balance to go into the negative when unexpired stock is 0 or insufficient.
     *
     * @param int $productId
     * @param int $quantity
     * @param float|null $overrideUnitPrice
     * @param int|null $preferredBatchId
     * @return array Array of allocated items: [['batch_id', 'batch_number', 'expiry_date', 'quantity', 'cost_price', 'unit_price', 'subtotal']]
     * @throws Exception
     */
    public function allocateAndDeduct(int $productId, int $quantity, ?float $overrideUnitPrice = null, ?int $preferredBatchId = null): array
    {
        if ($quantity <= 0) {
            throw new Exception("Quantity to allocate must be greater than zero.");
        }

        $today = now()->toDateString();
        $product = Product::find($productId);

        // Pessimistic lock on positive-stock unexpired batches ordered by earliest expiry date
        $batches = ProductBatch::where('product_id', $productId)
            ->where('expiry_date', '>', $today)
            ->where('stock_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->lockForUpdate()
            ->get();

        // Prioritize the batch matching the selected batch ID or selected batch price first (then FEFO)
        if ($batches->count() > 1 && ($preferredBatchId !== null || ($overrideUnitPrice !== null && $overrideUnitPrice > 0))) {
            $batches = $batches->sortBy(function ($b) use ($preferredBatchId, $overrideUnitPrice) {
                if ($preferredBatchId !== null && (int) $b->id === (int) $preferredBatchId) {
                    return '0_' . $b->expiry_date->format('Ymd');
                }
                if ($overrideUnitPrice !== null && abs((float) $b->selling_price - (float) $overrideUnitPrice) < 0.005) {
                    return '1_' . $b->expiry_date->format('Ymd');
                }
                return '2_' . $b->expiry_date->format('Ymd');
            })->values();
        }

        $remainingToAllocate = $quantity;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $deductQty = min($batch->stock_quantity, $remainingToAllocate);
            $batch->stock_quantity -= $deductQty;
            $batch->save();

            // Use the transaction's selected unit price first, then product/batch price
            $unitPrice = ($overrideUnitPrice !== null && (float) $overrideUnitPrice > 0)
                ? (float) $overrideUnitPrice
                : ((float) ($product?->unit_retail_price ?? 0) > 0
                    ? (float) $product->unit_retail_price
                    : (float) $batch->selling_price);
            $costPrice = (float) $batch->purchase_cost > 0
                ? (float) $batch->purchase_cost
                : (float) ($product?->unit_cost_price ?? 0);
            $subtotal = round($deductQty * $unitPrice, 2);

            $allocations[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date->toDateString(),
                'quantity' => $deductQty,
                'cost_price' => $costPrice,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];

            $remainingToAllocate -= $deductQty;
        }

        // If stock was insufficient, 0, or already negative, allow balance to go negative
        if ($remainingToAllocate > 0) {
            if ($batches->isNotEmpty()) {
                // Deduct the deficit from the last batch we just depleted so its stock goes negative
                $lastBatch = $batches->last();
                $lastBatch->stock_quantity -= $remainingToAllocate;
                $lastBatch->save();

                $lastIndex = count($allocations) - 1;
                $allocations[$lastIndex]['quantity'] += $remainingToAllocate;
                $allocations[$lastIndex]['subtotal'] = round(
                    $allocations[$lastIndex]['quantity'] * $allocations[$lastIndex]['unit_price'],
                    2
                );
            } else {
                // Find an existing unexpired batch (even with <= 0 stock), or any batch for this product
                $fallbackBatch = ProductBatch::where('product_id', $productId)
                    ->where('expiry_date', '>', $today)
                    ->orderBy('expiry_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                if (!$fallbackBatch) {
                    $fallbackBatch = ProductBatch::where('product_id', $productId)
                        ->orderBy('expiry_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->lockForUpdate()
                        ->first();
                }

                if ($fallbackBatch) {
                    $fallbackBatch->stock_quantity -= $remainingToAllocate;
                    $fallbackBatch->save();
                } else {
                    // Product has no batch records yet; create an automatic batch with negative balance
                    $fallbackBatch = ProductBatch::create([
                        'product_id' => $productId,
                        'batch_number' => 'AUTO-' . date('Ymd'),
                        'expiry_date' => now()->addYears(2)->toDateString(),
                        'purchase_cost' => (float) ($product?->unit_cost_price ?? 0),
                        'selling_price' => (float) ($overrideUnitPrice ?? $product?->unit_retail_price ?? 0),
                        'stock_quantity' => -$remainingToAllocate,
                    ]);
                }

                $unitPrice = ($overrideUnitPrice !== null && (float) $overrideUnitPrice > 0)
                    ? (float) $overrideUnitPrice
                    : ((float) ($product?->unit_retail_price ?? 0) > 0
                        ? (float) $product->unit_retail_price
                        : (float) $fallbackBatch->selling_price);
                $costPrice = (float) $fallbackBatch->purchase_cost > 0
                    ? (float) $fallbackBatch->purchase_cost
                    : (float) ($product?->unit_cost_price ?? 0);
                $subtotal = round($remainingToAllocate * $unitPrice, 2);

                $allocations[] = [
                    'batch_id' => $fallbackBatch->id,
                    'batch_number' => $fallbackBatch->batch_number,
                    'expiry_date' => $fallbackBatch->expiry_date->toDateString(),
                    'quantity' => $remainingToAllocate,
                    'cost_price' => $costPrice,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return $allocations;
    }

    /**
     * Inspect FEFO preview for cart items without deducting stock.
     */
    public function previewFEFO(int $productId, int $quantity): array
    {
        $today = now()->toDateString();

        $batches = ProductBatch::where('product_id', $productId)
            ->where('expiry_date', '>', $today)
            ->where('stock_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->get();

        $totalAvailable = $batches->sum('stock_quantity');
        $sufficient = ($totalAvailable >= $quantity);

        $breakdown = [];
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($batch->stock_quantity, $remaining);
            $breakdown[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date->toDateString(),
                'available' => $batch->stock_quantity,
                'allocated' => $take,
                'selling_price' => (float) $batch->selling_price,
            ];
            $remaining -= $take;
        }

        return [
            'product_id' => $productId,
            'requested' => $quantity,
            'total_unexpired' => $totalAvailable,
            'sufficient' => $sufficient,
            'batches' => $breakdown,
        ];
    }
}
