<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReturnController extends Controller
{
    /**
     * Display the unified Returns Hub (Customer Sales Returns & Shop GRN Returns).
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'customer');

        $saleReturns = SaleReturn::with(['sale', 'customer', 'processor', 'items.product'])
            ->latest()
            ->paginate(15, ['*'], 'customer_page');

        $purchaseReturns = PurchaseReturn::with(['grn', 'supplier', 'processor', 'items.product'])
            ->latest()
            ->paginate(15, ['*'], 'supplier_page');

        $stats = [
            'today_customer_returns' => (float) SaleReturn::whereDate('created_at', today())->sum('total_refund_amount'),
            'today_customer_returns_count' => SaleReturn::whereDate('created_at', today())->count(),
            'total_customer_returns' => (float) SaleReturn::sum('total_refund_amount'),
            'total_supplier_returns' => (float) PurchaseReturn::sum('total_return_amount'),
            'total_supplier_returns_count' => PurchaseReturn::count(),
        ];

        return view('returns.index', compact('tab', 'saleReturns', 'purchaseReturns', 'stats'));
    }

    /**
     * Show Customer Billed Item Return form.
     */
    public function createCustomerReturn(Request $request)
    {
        $recentSales = Sale::with(['customer', 'items.product'])
            ->where('status', 'completed')
            ->latest()
            ->limit(25)
            ->get();

        $preselectedSale = null;
        if ($request->filled('sale_id')) {
            $sale = Sale::with(['customer', 'items.product', 'items.batch'])->find($request->get('sale_id'));
            if ($sale) {
                $preselectedSale = $this->formatSaleForReturn($sale);
            }
        } elseif ($request->filled('invoice')) {
            $sale = Sale::with(['customer', 'items.product', 'items.batch'])
                ->where('invoice_number', trim($request->get('invoice')))
                ->first();
            if ($sale) {
                $preselectedSale = $this->formatSaleForReturn($sale);
            }
        }

        return view('returns.customer_create', compact('recentSales', 'preselectedSale'));
    }

    /**
     * AJAX Lookup of a POS Sale / Invoice for Customer Return.
     */
    public function lookupSale(Request $request)
    {
        $q = trim($request->get('q', ''));
        $saleId = $request->get('sale_id');

        if ($saleId) {
            $sale = Sale::with(['customer', 'items.product', 'items.batch'])->find($saleId);
            if (!$sale) {
                return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
            }
            return response()->json([
                'success' => true,
                'sale' => $this->formatSaleForReturn($sale),
            ]);
        }

        if (empty($q)) {
            return response()->json(['success' => false, 'message' => 'Please enter an invoice number or customer name.'], 422);
        }

        // Exact or partial match on invoice_number or customer name/phone
        $sales = Sale::with(['customer', 'items.product', 'items.batch'])
            ->where('status', 'completed')
            ->where(function ($query) use ($q) {
                $query->where('invoice_number', 'like', "%{$q}%")
                      ->orWhereHas('customer', function ($cq) use ($q) {
                          $cq->where('name', 'like', "%{$q}%")
                             ->orWhere('phone', 'like', "%{$q}%");
                      });
            })
            ->latest()
            ->limit(10)
            ->get();

        if ($sales->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "No completed invoice found matching '{$q}'.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'sale' => $this->formatSaleForReturn($sales->first()),
            'matches' => $sales->map(fn($s) => [
                'id' => $s->id,
                'invoice_number' => $s->invoice_number,
                'date' => $s->created_at->format('d M Y, h:i A'),
                'customer_name' => $s->customer ? $s->customer->name : 'Walk-in Customer',
                'grand_total' => (float) $s->grand_total,
            ]),
        ]);
    }

    /**
     * Format a Sale with its items and already-returned quantities.
     */
    protected function formatSaleForReturn(Sale $sale): array
    {
        $returnedBySaleItem = SaleReturnItem::whereIn('sale_item_id', $sale->items->pluck('id'))
            ->select('sale_item_id', DB::raw('SUM(quantity_returned) as total_returned'))
            ->groupBy('sale_item_id')
            ->pluck('total_returned', 'sale_item_id');

        $items = $sale->items->map(function (SaleItem $item) use ($returnedBySaleItem) {
            $alreadyReturned = (int) ($returnedBySaleItem[$item->id] ?? 0);
            $remainingQty = max(0, (int) $item->quantity - $alreadyReturned);
            $unitsPerPack = max(1, (int) ($item->product?->units_per_pack ?? 1));

            return [
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'brand_name' => $item->product?->brand_name ?? 'Unknown Item',
                'generic_name' => $item->product?->generic_name ?? '',
                'base_uom' => $item->product?->base_uom ?? 'Unit',
                'units_per_pack' => $unitsPerPack,
                'batch_id' => $item->batch_id,
                'batch_number' => $item->batch?->batch_number ?? 'N/A',
                'sold_qty' => (int) $item->quantity,
                'already_returned_qty' => $alreadyReturned,
                'remaining_qty' => $remainingQty,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'return_uom' => 'unit',
                'return_input_qty' => 0,
                'restock_status' => 'restocked',
            ];
        })->values();

        return [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'date' => $sale->created_at->format('d M Y, h:i A'),
            'payment_type' => $sale->payment_type,
            'subtotal' => (float) $sale->subtotal,
            'discount_amount' => (float) $sale->discount_amount,
            'grand_total' => (float) $sale->grand_total,
            'customer_id' => $sale->customer_id,
            'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
            'customer_phone' => $sale->customer ? $sale->customer->phone : null,
            'customer_debt' => $sale->customer ? (float) $sale->customer->current_debt_balance : 0.0,
            'items' => $items,
        ];
    }

    /**
     * Process and store a Customer Sales Return.
     */
    public function storeCustomerReturn(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'refund_method' => 'required|in:cash,credit_reduction,exchange',
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.return_uom' => 'nullable|in:unit,pack',
            'items.*.return_qty' => 'required|integer|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.restock_status' => 'required|in:restocked,discarded',
        ]);

        try {
            $saleReturn = DB::transaction(function () use ($validated) {
                $sale = Sale::with(['customer', 'items.product'])->lockForUpdate()->findOrFail($validated['sale_id']);

                $returnItemsPayload = [];
                $totalRefund = 0.0;

                foreach ($validated['items'] as $row) {
                    $inputQty = (int) ($row['return_qty'] ?? 0);
                    if ($inputQty <= 0) {
                        continue;
                    }

                    $saleItem = $sale->items->firstWhere('id', (int) $row['sale_item_id']);
                    if (!$saleItem) {
                        throw new Exception("Invalid sale item selected for return.");
                    }

                    $unitsPerPack = max(1, (int) ($saleItem->product?->units_per_pack ?? 1));
                    $returnUom = $row['return_uom'] ?? 'unit';
                    $baseQtyToReturn = ($returnUom === 'pack' && $unitsPerPack > 1)
                        ? ($inputQty * $unitsPerPack)
                        : $inputQty;

                    $alreadyReturned = (int) SaleReturnItem::where('sale_item_id', $saleItem->id)->sum('quantity_returned');
                    $maxReturnable = max(0, (int) $saleItem->quantity - $alreadyReturned);

                    if ($baseQtyToReturn > $maxReturnable) {
                        $drugName = $saleItem->product?->brand_name ?? 'Item';
                        throw new Exception("Cannot return {$baseQtyToReturn} units of '{$drugName}'. Maximum returnable quantity is {$maxReturnable}.");
                    }

                    $unitPrice = (float) $row['unit_price'];
                    $lineSubtotal = round($baseQtyToReturn * $unitPrice, 2);
                    $totalRefund += $lineSubtotal;

                    // Restock back to inventory batch if in good condition
                    if ($row['restock_status'] === 'restocked') {
                        $batch = null;
                        if ($saleItem->batch_id) {
                            $batch = ProductBatch::where('id', $saleItem->batch_id)->lockForUpdate()->first();
                        }
                        if (!$batch) {
                            $batch = ProductBatch::where('product_id', $saleItem->product_id)
                                ->orderBy('expiry_date', 'desc')
                                ->lockForUpdate()
                                ->first();
                        }
                        if ($batch) {
                            $batch->stock_quantity += $baseQtyToReturn;
                            $batch->save();
                        }
                    }

                    $returnItemsPayload[] = [
                        'sale_item_id' => $saleItem->id,
                        'product_id' => $saleItem->product_id,
                        'batch_id' => $saleItem->batch_id,
                        'quantity_returned' => $baseQtyToReturn,
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                        'restock_status' => $row['restock_status'],
                    ];
                }

                if (empty($returnItemsPayload)) {
                    throw new Exception("Please enter a return quantity greater than 0 for at least one billed item.");
                }

                // Adjust customer debt balance if credit_reduction selected
                if ($validated['refund_method'] === 'credit_reduction') {
                    if (!$sale->customer_id) {
                        throw new Exception("Walk-in customer invoices cannot use 'Reduce Customer Debt'. Choose Cash Refund or Exchange.");
                    }
                    $customer = Customer::where('id', $sale->customer_id)->lockForUpdate()->firstOrFail();
                    $customer->current_debt_balance = max(0.0, (float) $customer->current_debt_balance - $totalRefund);
                    $customer->save();
                }

                $returnNumber = 'SR-' . date('Ymd') . '-' . strtoupper(Str::random(4));

                $saleReturn = SaleReturn::create([
                    'return_number' => $returnNumber,
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'processed_by' => Auth::id() ?? 1,
                    'total_refund_amount' => round($totalRefund, 2),
                    'refund_method' => $validated['refund_method'],
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($returnItemsPayload as $itemData) {
                    $itemData['sale_return_id'] = $saleReturn->id;
                    SaleReturnItem::create($itemData);
                }

                return $saleReturn;
            });

            return redirect()->route('returns.customer.show', $saleReturn->id)
                ->with('success', "Customer Return #{$saleReturn->return_number} processed and stock updated successfully.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show Customer Sales Return Credit Note / Receipt.
     */
    public function showCustomerReturn(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale', 'customer', 'processor', 'items.product', 'items.batch']);
        return view('returns.customer_show', compact('saleReturn'));
    }

    /**
     * Show Shop / Supplier GRN Return creation form.
     */
    public function createSupplierReturn(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $recentGrns = Grn::with(['supplier', 'items.product'])
            ->latest()
            ->limit(40)
            ->get();

        $products = Product::with(['batches' => function ($q) {
            $q->orderBy('expiry_date', 'asc');
        }])->orderBy('brand_name')->get();

        $preselectedGrn = null;
        if ($request->filled('grn_id')) {
            $grn = Grn::with(['supplier', 'items.product'])->find($request->get('grn_id'));
            if ($grn) {
                $preselectedGrn = $this->formatGrnForReturn($grn);
            }
        }

        return view('returns.supplier_create', compact('suppliers', 'recentGrns', 'products', 'preselectedGrn'));
    }

    /**
     * AJAX Lookup of a GRN for Shop / Supplier Return.
     */
    public function lookupGrn(Request $request)
    {
        $grnId = $request->get('grn_id');
        $grn = Grn::with(['supplier', 'items.product'])->find($grnId);

        if (!$grn) {
            return response()->json(['success' => false, 'message' => 'GRN not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'grn' => $this->formatGrnForReturn($grn),
        ]);
    }

    /**
     * Format a GRN with its received items, batch stock, and already-returned quantities.
     */
    protected function formatGrnForReturn(Grn $grn): array
    {
        $returnedByGrnItem = PurchaseReturnItem::whereIn('grn_item_id', $grn->items->pluck('id'))
            ->select('grn_item_id', DB::raw('SUM(quantity_returned) as total_returned'))
            ->groupBy('grn_item_id')
            ->pluck('total_returned', 'grn_item_id');

        $items = $grn->items->map(function (GrnItem $item) use ($returnedByGrnItem) {
            $batch = ProductBatch::where('product_id', $item->product_id)
                ->where('batch_number', $item->batch_number)
                ->first();

            $totalReceived = (int) $item->quantity_received + (int) $item->free_bonus_qty;
            $alreadyReturned = (int) ($returnedByGrnItem[$item->id] ?? 0);
            $remainingReturnable = max(0, $totalReceived - $alreadyReturned);
            $unitsPerPack = max(1, (int) ($item->units_per_pack ?? $item->product?->units_per_pack ?? 1));

            return [
                'grn_item_id' => $item->id,
                'product_id' => $item->product_id,
                'brand_name' => $item->product?->brand_name ?? 'Unknown Item',
                'base_uom' => $item->product?->base_uom ?? 'Tablet',
                'units_per_pack' => $unitsPerPack,
                'batch_id' => $batch?->id,
                'batch_number' => $item->batch_number,
                'expiry_date' => $item->expiry_date ? $item->expiry_date->format('Y-m-d') : null,
                'purchase_uom' => $item->purchase_uom ?? 'unit',
                'received_base_qty' => $totalReceived,
                'already_returned_qty' => $alreadyReturned,
                'remaining_returnable_qty' => $remainingReturnable,
                'current_batch_stock' => $batch ? (int) $batch->stock_quantity : 0,
                'unit_cost' => (float) $item->cost_price,
                'pack_cost' => round((float) $item->cost_price * $unitsPerPack, 2),
                'return_uom' => ($item->purchase_uom === 'pack' && $unitsPerPack > 1) ? 'pack' : 'unit',
                'return_input_qty' => 0,
            ];
        })->values();

        return [
            'id' => $grn->id,
            'grn_number' => $grn->grn_number,
            'invoice_number' => $grn->invoice_number,
            'invoice_date' => $grn->invoice_date ? $grn->invoice_date->format('d M Y') : '',
            'supplier_id' => $grn->supplier_id,
            'supplier_name' => $grn->supplier?->name ?? 'Unknown Supplier',
            'supplier_balance' => (float) ($grn->supplier?->current_balance ?? 0),
            'total_amount' => (float) $grn->total_amount,
            'items' => $items,
        ];
    }

    /**
     * Store a Shop / Supplier GRN Return (Purchase Return / Debit Note).
     */
    public function storeSupplierReturn(Request $request)
    {
        $validated = $request->validate([
            'grn_id' => 'nullable|exists:grns,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'settlement_method' => 'required|in:payable_deduction,cash_refund,replacement',
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.grn_item_id' => 'nullable|exists:grn_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'nullable|exists:product_batches,id',
            'items.*.batch_number' => 'required|string|max:80',
            'items.*.return_uom' => 'required|in:unit,pack',
            'items.*.units_per_pack' => 'nullable|integer|min:1',
            'items.*.return_qty' => 'required|integer|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            $purchaseReturn = DB::transaction(function () use ($validated) {
                $supplier = Supplier::where('id', $validated['supplier_id'])->lockForUpdate()->firstOrFail();

                $returnItemsPayload = [];
                $totalReturnAmount = 0.0;

                foreach ($validated['items'] as $row) {
                    $inputQty = (int) ($row['return_qty'] ?? 0);
                    if ($inputQty <= 0) {
                        continue;
                    }

                    $product = Product::findOrFail($row['product_id']);
                    $returnUom = $row['return_uom'] ?? 'unit';
                    $unitsPerPack = max(1, (int) ($row['units_per_pack'] ?? $product->units_per_pack ?? 1));

                    if ($returnUom === 'pack') {
                        $packsReturned = $inputQty;
                        $baseQtyReturned = $inputQty * $unitsPerPack;
                    } else {
                        $packsReturned = 0;
                        $baseQtyReturned = $inputQty;
                    }

                    // If linked to a specific GRN item, verify against remaining returnable quantity from that GRN
                    if (!empty($row['grn_item_id'])) {
                        $grnItem = GrnItem::find($row['grn_item_id']);
                        if ($grnItem) {
                            $totalReceived = (int) $grnItem->quantity_received + (int) $grnItem->free_bonus_qty;
                            $alreadyReturned = (int) PurchaseReturnItem::where('grn_item_id', $grnItem->id)->sum('quantity_returned');
                            $maxReturnable = max(0, $totalReceived - $alreadyReturned);
                            if ($baseQtyReturned > $maxReturnable) {
                                throw new Exception("Cannot return {$baseQtyReturned} {$product->base_uom} of '{$product->brand_name}'. Remaining returnable quantity on this GRN is {$maxReturnable}.");
                            }
                        }
                    }

                    // Locate and lock the inventory batch to deduct stock
                    $batch = null;
                    if (!empty($row['batch_id'])) {
                        $batch = ProductBatch::where('id', $row['batch_id'])->lockForUpdate()->first();
                    }
                    if (!$batch) {
                        $batch = ProductBatch::where('product_id', $product->id)
                            ->where('batch_number', $row['batch_number'])
                            ->lockForUpdate()
                            ->first();
                    }

                    if ($batch) {
                        $batch->stock_quantity -= $baseQtyReturned;
                        $batch->save();
                    }

                    $unitCost = (float) $row['unit_cost'];
                    $lineSubtotal = round($baseQtyReturned * $unitCost, 2);
                    $totalReturnAmount += $lineSubtotal;

                    $returnItemsPayload[] = [
                        'grn_item_id' => $row['grn_item_id'] ?? null,
                        'product_id' => $product->id,
                        'batch_id' => $batch?->id,
                        'batch_number' => $row['batch_number'],
                        'return_uom' => $returnUom,
                        'units_per_pack' => $unitsPerPack,
                        'packs_returned' => $packsReturned,
                        'quantity_returned' => $baseQtyReturned,
                        'unit_cost' => $unitCost,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                if (empty($returnItemsPayload)) {
                    throw new Exception("Please enter a return quantity greater than 0 for at least one GRN / batch item.");
                }

                // Deduct from Supplier Accounts Payable balance if payable_deduction selected
                if ($validated['settlement_method'] === 'payable_deduction') {
                    $supplier->current_balance = max(0.0, (float) $supplier->current_balance - $totalReturnAmount);
                    $supplier->save();
                }

                $returnNumber = 'PR-' . date('Ymd') . '-' . strtoupper(Str::random(4));

                $purchaseReturn = PurchaseReturn::create([
                    'return_number' => $returnNumber,
                    'grn_id' => $validated['grn_id'] ?? null,
                    'supplier_id' => $supplier->id,
                    'processed_by' => Auth::id() ?? 1,
                    'total_return_amount' => round($totalReturnAmount, 2),
                    'settlement_method' => $validated['settlement_method'],
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($returnItemsPayload as $itemData) {
                    $itemData['purchase_return_id'] = $purchaseReturn->id;
                    PurchaseReturnItem::create($itemData);
                }

                return $purchaseReturn;
            });

            return redirect()->route('returns.supplier.show', $purchaseReturn->id)
                ->with('success', "Supplier GRN Return #{$purchaseReturn->return_number} processed and stock deducted successfully.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show Supplier GRN Return / Debit Note Voucher.
     */
    public function showSupplierReturn(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['grn', 'supplier', 'processor', 'items.product', 'items.batch']);
        return view('returns.supplier_show', compact('purchaseReturn'));
    }
}
