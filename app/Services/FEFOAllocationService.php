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
     *
     * @param int $productId
     * @param int $quantity
     * @return array Array of allocated items: [['batch_id', 'batch_number', 'expiry_date', 'quantity', 'cost_price', 'unit_price', 'subtotal']]
     * @throws Exception
     */
    public function allocateAndDeduct(int $productId, int $quantity): array
    {
        if ($quantity <= 0) {
            throw new Exception("Quantity to allocate must be greater than zero.");
        }

        $today = now()->toDateString();

        // Pessimistic lock on batches for this product ordered by earliest expiry date
        $batches = ProductBatch::where('product_id', $productId)
            ->where('expiry_date', '>', $today)
            ->where('stock_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->lockForUpdate()
            ->get();

        $totalAvailable = $batches->sum('stock_quantity');

        if ($totalAvailable < $quantity) {
            $product = Product::find($productId);
            $name = $product ? $product->brand_name : "Product #{$productId}";
            throw new Exception("Insufficient unexpired stock for '{$name}'. Requested: {$quantity}, Unexpired Available: {$totalAvailable}.");
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

            $subtotal = round($deductQty * (float) $batch->selling_price, 2);

            $allocations[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date->toDateString(),
                'quantity' => $deductQty,
                'cost_price' => (float) $batch->purchase_cost,
                'unit_price' => (float) $batch->selling_price,
                'subtotal' => $subtotal,
            ];

            $remainingToAllocate -= $deductQty;
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
