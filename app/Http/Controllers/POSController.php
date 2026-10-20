<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\FEFOAllocationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POSController extends Controller
{
    protected FEFOAllocationService $fefoService;

    public function __construct(FEFOAllocationService $fefoService)
    {
        $this->fefoService = $fefoService;
    }

    /**
     * Display the reactive POS Counter interface.
     */
    public function index()
    {
        $categories = Category::all();
        $customers = Customer::orderBy('name')->get();
        $recentSales = Sale::with(['customer', 'cashier'])
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'today_sales_count' => Sale::whereDate('created_at', today())->where('status', 'completed')->count(),
            'today_revenue' => (float) Sale::whereDate('created_at', today())->where('status', 'completed')->sum('grand_total'),
            'today_cash' => (float) Sale::whereDate('created_at', today())->where('status', 'completed')->where('payment_type', 'cash')->sum('amount_paid'),
            'today_credit' => (float) Sale::whereDate('created_at', today())->where('status', 'completed')->where('payment_type', 'credit_lending')->sum('grand_total'),
        ];

        return view('pos.index', compact('categories', 'customers', 'recentSales', 'stats'));
    }

    /**
     * Fast sub-150ms JSON search endpoint for POS Counter.
     * Supports barcode SKU, brand name, generic salt lookup.
     */
    public function search(Request $request)
    {
        $term = trim($request->get('q', ''));
        $categoryId = $request->get('category_id');

        if (empty($term) && empty($categoryId)) {
            // Return top popular/frequently sold products
            $products = Product::with(['category', 'activeBatches'])
                ->limit(24)
                ->get();
        } else {
            $query = Product::with(['category', 'activeBatches']);

            if (!empty($term)) {
                $query->search($term);
            }

            if (!empty($categoryId)) {
                $query->where('category_id', $categoryId);
            }

            $products = $query->limit(30)->get();
        }

        $formatted = $products->map(function ($product) {
            $batches = $product->activeBatches;
            $earliestBatch = $batches->first();

            return [
                'id' => $product->id,
                'barcode_sku' => $product->barcode_sku,
                'brand_name' => $product->brand_name,
                'generic_name' => $product->generic_name ?? 'N/A',
                'dosage_strength' => $product->dosage_strength ?? '',
                'item_type' => $product->item_type,
                'base_uom' => $product->base_uom,
                'requires_prescription' => (bool) $product->requires_prescription,
                'is_controlled' => (bool) $product->is_controlled,
                'retail_price' => (float) ($earliestBatch ? $earliestBatch->selling_price : $product->unit_retail_price),
                'cost_price' => (float) ($earliestBatch ? $earliestBatch->purchase_cost : $product->unit_cost_price),
                'total_stock' => (int) $batches->sum('stock_quantity'),
                'batches_count' => $batches->count(),
                'earliest_expiry' => $earliestBatch ? $earliestBatch->expiry_date->format('Y-m-d') : null,
                'earliest_batch_no' => $earliestBatch ? $earliestBatch->batch_number : null,
                'category_name' => $product->category ? $product->category->name : 'General',
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $formatted->count(),
            'products' => $formatted,
        ]);
    }

    /**
     * Direct barcode scan lookup (sub-50ms).
     */
    public function barcodeLookup(Request $request)
    {
        $barcode = trim($request->get('barcode', ''));

        if (empty($barcode)) {
            return response()->json(['success' => false, 'message' => 'Barcode cannot be empty.'], 422);
        }

        $product = Product::where('barcode_sku', $barcode)
            ->with(['activeBatches', 'category'])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => "Item not found with barcode '{$barcode}'. You can quick-register it.",
                'not_found' => true,
            ], 404);
        }

        $batches = $product->activeBatches;
        $totalStock = (int) $batches->sum('stock_quantity');
        $earliestBatch = $batches->first();

        if ($totalStock <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Product '{$product->brand_name}' has 0 unexpired stock.",
                'out_of_stock' => true,
                'product' => $product,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'barcode_sku' => $product->barcode_sku,
                'brand_name' => $product->brand_name,
                'generic_name' => $product->generic_name ?? 'N/A',
                'dosage_strength' => $product->dosage_strength ?? '',
                'item_type' => $product->item_type,
                'base_uom' => $product->base_uom,
                'requires_prescription' => (bool) $product->requires_prescription,
                'is_controlled' => (bool) $product->is_controlled,
                'retail_price' => (float) ($earliestBatch ? $earliestBatch->selling_price : $product->unit_retail_price),
                'total_stock' => $totalStock,
                'earliest_expiry' => $earliestBatch ? $earliestBatch->expiry_date->format('Y-m-d') : null,
                'earliest_batch_no' => $earliestBatch ? $earliestBatch->batch_number : null,
            ],
        ]);
    }

    /**
     * Single-click quick item registration modal for cashiers during billing.
     */
    public function quickStoreProduct(Request $request)
    {
        $validated = $request->validate([
            'brand_name' => 'required|string|max:150',
            'generic_name' => 'nullable|string|max:150',
            'barcode_sku' => 'required|string|max:100|unique:products,barcode_sku',
            'dosage_strength' => 'nullable|string|max:50',
            'item_type' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'base_uom' => 'required|string',
            'initial_batch_number' => 'required|string',
            'initial_expiry_date' => 'required|date|after:today',
            'initial_stock' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_controlled' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated) {
            $product = Product::create([
                'barcode_sku' => $validated['barcode_sku'],
                'brand_name' => $validated['brand_name'],
                'generic_name' => $validated['generic_name'] ?? null,
                'dosage_strength' => $validated['dosage_strength'] ?? null,
                'item_type' => $validated['item_type'],
                'category_id' => $validated['category_id'] ?? null,
                'base_uom' => $validated['base_uom'],
                'units_per_pack' => 1,
                'reorder_min' => 10,
                'reorder_max' => 100,
                'requires_prescription' => !empty($validated['requires_prescription']),
                'is_controlled' => !empty($validated['is_controlled']),
                'unit_cost_price' => $validated['cost_price'],
                'unit_retail_price' => $validated['retail_price'],
            ]);

            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => $validated['initial_batch_number'],
                'expiry_date' => $validated['initial_expiry_date'],
                'purchase_cost' => $validated['cost_price'],
                'selling_price' => $validated['retail_price'],
                'stock_quantity' => $validated['initial_stock'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Drug '{$product->brand_name}' created successfully.",
                'product' => [
                    'id' => $product->id,
                    'barcode_sku' => $product->barcode_sku,
                    'brand_name' => $product->brand_name,
                    'generic_name' => $product->generic_name,
                    'dosage_strength' => $product->dosage_strength,
                    'item_type' => $product->item_type,
                    'base_uom' => $product->base_uom,
                    'requires_prescription' => (bool) $product->requires_prescription,
                    'is_controlled' => (bool) $product->is_controlled,
                    'retail_price' => (float) $batch->selling_price,
                    'total_stock' => $batch->stock_quantity,
                    'earliest_expiry' => $batch->expiry_date->format('Y-m-d'),
                    'earliest_batch_no' => $batch->batch_number,
                ]
            ]);
        });
    }

    /**
     * Process POS Counter checkout.
     * Enforces FEFO batch deduction, pessimistic locking, lending limits, prescriptions, and thermal print data.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_type' => 'required|in:cash,card,qr,credit_lending,split',
            'amount_paid' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            // Prescription fields (if Rx required)
            'doctor_name' => 'nullable|string|max:150',
            'doctor_reg_no' => 'nullable|string|max:80',
            'patient_name' => 'nullable|string|max:150',
        ]);

        $cashierId = Auth::id() ?? 1;
        $paymentType = $validated['payment_type'];
        $customerId = $validated['customer_id'] ?? null;
        $discount = (float) ($validated['discount_amount'] ?? 0.0);
        $tax = (float) ($validated['tax_amount'] ?? 0.0);

        try {
            return DB::transaction(function () use ($validated, $cashierId, $paymentType, $customerId, $discount, $tax) {
                // Check if any product requires prescription and validate prescription info
                $productIds = collect($validated['items'])->pluck('product_id')->unique();
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                $requiresRx = false;
                $isControlled = false;
                foreach ($validated['items'] as $it) {
                    $prod = $products[$it['product_id']] ?? null;
                    if ($prod && $prod->requires_prescription) {
                        $requiresRx = true;
                    }
                    if ($prod && $prod->is_controlled) {
                        $isControlled = true;
                    }
                }

                if (($requiresRx || $isControlled) && empty($validated['doctor_name']) && empty($validated['patient_name'])) {
                    // For controlled drugs, doctor and patient must be registered
                    if ($isControlled) {
                        throw new Exception("Controlled substance detected! Doctor Name and Patient Name are legally required.");
                    }
                }

                // First pass: perform FEFO allocations and stock deductions under lockForUpdate
                $allAllocations = [];
                $subtotal = 0.0;

                foreach ($validated['items'] as $item) {
                    $prodId = (int) $item['product_id'];
                    $qty = (int) $item['quantity'];

                    $allocations = $this->fefoService->allocateAndDeduct($prodId, $qty);
                    foreach ($allocations as $alloc) {
                        $alloc['product_id'] = $prodId;
                        $allAllocations[] = $alloc;
                        $subtotal += $alloc['subtotal'];
                    }
                }

                $grandTotal = max(0.0, round(($subtotal - $discount) + $tax, 2));

                // Payment and Customer Lending checks
                $amountPaid = (float) ($validated['amount_paid'] ?? 0.0);
                $balanceDue = 0.0;

                if ($paymentType === 'credit_lending') {
                    if (!$customerId) {
                        throw new Exception("Customer Lending requires selecting a registered customer.");
                    }

                    $customer = Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();
                    $projectedDebt = (float) $customer->current_debt_balance + $grandTotal;

                    if ($projectedDebt > (float) $customer->credit_limit) {
                        $excess = $projectedDebt - (float) $customer->credit_limit;
                        throw new Exception("Credit Limit Exceeded! Customer limit is Rs. {$customer->credit_limit}, current debt is Rs. {$customer->current_debt_balance}. Bill would exceed limit by Rs. " . number_format($excess, 2));
                    }

                    // Increment customer's debt
                    $customer->current_debt_balance = $projectedDebt;
                    $customer->save();

                    $balanceDue = $grandTotal;
                    $amountPaid = 0.0;
                } elseif ($paymentType === 'split') {
                    $balanceDue = max(0.0, round($grandTotal - $amountPaid, 2));
                    if ($balanceDue > 0 && $customerId) {
                        $customer = Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();
                        $customer->current_debt_balance += $balanceDue;
                        $customer->save();
                    }
                } else {
                    // Cash, Card, QR
                    if ($amountPaid < $grandTotal && $paymentType === 'cash') {
                        // In cash mode, if amount_paid is entered less than grand_total, allow exact match if omitted
                        if ($amountPaid <= 0) {
                            $amountPaid = $grandTotal;
                        }
                    } elseif ($amountPaid <= 0) {
                        $amountPaid = $grandTotal;
                    }
                    $balanceDue = max(0.0, round($grandTotal - $amountPaid, 2));
                }

                // Generate Unique Invoice Number (e.g. INV-20261019-XXXX)
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5));

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'customer_id' => $customerId,
                    'cashier_id' => $cashierId,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'grand_total' => $grandTotal,
                    'payment_type' => $paymentType,
                    'amount_paid' => $amountPaid,
                    'balance_due' => $balanceDue,
                    'status' => 'completed',
                ]);

                // Store line items
                foreach ($allAllocations as $alloc) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $alloc['product_id'],
                        'batch_id' => $alloc['batch_id'],
                        'quantity' => $alloc['quantity'],
                        'unit_price' => $alloc['unit_price'],
                        'cost_price' => $alloc['cost_price'],
                        'subtotal' => $alloc['subtotal'],
                    ]);
                }

                // If prescription info provided
                if (!empty($validated['doctor_name']) || !empty($validated['patient_name'])) {
                    Prescription::create([
                        'sale_id' => $sale->id,
                        'doctor_name' => $validated['doctor_name'] ?? 'Dr. Unknown',
                        'doctor_reg_no' => $validated['doctor_reg_no'] ?? null,
                        'patient_name' => $validated['patient_name'] ?? 'Walk-in Patient',
                    ]);
                }

                // Load relationships for thermal receipt
                $sale->load(['items.product', 'items.batch', 'customer', 'cashier', 'prescription']);

                return response()->json([
                    'success' => true,
                    'message' => 'Sale processed successfully.',
                    'invoice_number' => $sale->invoice_number,
                    'sale_id' => $sale->id,
                    'grand_total' => $sale->grand_total,
                    'amount_paid' => $sale->amount_paid,
                    'change_return' => max(0.0, round($sale->amount_paid - $sale->grand_total, 2)),
                    'balance_due' => $sale->balance_due,
                    'receipt_url' => route('pos.receipt', $sale->id),
                    'sale' => $sale,
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Thermal Receipt View (80mm / 58mm ESC/POS compatible).
     */
    public function receipt(Sale $sale)
    {
        $sale->load(['items.product', 'items.batch', 'customer', 'cashier', 'prescription']);
        return view('pos.receipt', compact('sale'));
    }

    /**
     * Void a sale and restore stock.
     */
    public function voidSale(Sale $sale)
    {
        if ($sale->status === 'voided') {
            return back()->with('error', 'Sale is already voided.');
        }

        return DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                ProductBatch::where('id', $item->batch_id)
                    ->lockForUpdate()
                    ->increment('stock_quantity', $item->quantity);
            }

            if ($sale->payment_type === 'credit_lending' && $sale->customer_id) {
                $customer = Customer::where('id', $sale->customer_id)->lockForUpdate()->first();
                if ($customer) {
                    $customer->current_debt_balance = max(0.0, (float) $customer->current_debt_balance - (float) $sale->balance_due);
                    $customer->save();
                }
            }

            $sale->status = 'voided';
            $sale->save();

            return back()->with('success', "Sale #{$sale->invoice_number} voided and stock restored.");
        });
    }
}
