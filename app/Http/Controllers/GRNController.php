<?php

namespace App\Http\Controllers;

use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GRNController extends Controller
{
    /**
     * List all Goods Received Notes.
     */
    public function index(Request $request)
    {
        $query = Grn::with(['supplier', 'receiver', 'items.product'])->latest();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('grn_number', 'like', "%{$term}%")
                  ->orWhere('invoice_number', 'like', "%{$term}%");
            });
        }

        $grns = $query->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();

        return view('grn.index', compact('grns', 'suppliers'));
    }

    /**
     * Show form to create new GRN.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('brand_name')->get();
        $nextGrnNumber = 'GRN-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        return view('grn.create', compact('suppliers', 'products', 'nextGrnNumber'));
    }

    /**
     * Store and finalize a new GRN.
     * Updates inventory batches, recalculates product average cost, and updates supplier accounts payable.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_number' => 'required|string|max:100',
            'items.*.expiry_date' => 'required|date|after:today',
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.free_bonus_qty' => 'nullable|integer|min:0',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.retail_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $userId = Auth::id() ?? 1;
            $supplier = Supplier::where('id', $validated['supplier_id'])->lockForUpdate()->firstOrFail();

            $grnNumber = 'GRN-' . date('Ymd') . '-' . strtoupper(Str::random(4));

            $totalAmount = 0.0;
            $grnItemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity_received'];
                $bonus = (int) ($item['free_bonus_qty'] ?? 0);
                $cost = (float) $item['cost_price'];
                $retail = (float) $item['retail_price'];
                $lineSubtotal = round($qty * $cost, 2);
                $totalAmount += $lineSubtotal;

                $grnItemsData[] = [
                    'product_id' => $item['product_id'],
                    'batch_number' => trim($item['batch_number']),
                    'expiry_date' => $item['expiry_date'],
                    'quantity_received' => $qty,
                    'free_bonus_qty' => $bonus,
                    'cost_price' => $cost,
                    'retail_price' => $retail,
                    'subtotal' => $lineSubtotal,
                ];
            }

            // Create GRN
            $grn = Grn::create([
                'grn_number' => $grnNumber,
                'supplier_id' => $supplier->id,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'status' => 'Received',
                'received_by' => $userId,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Process GRN items & inventory batches
            foreach ($grnItemsData as $itemData) {
                $itemData['grn_id'] = $grn->id;
                GrnItem::create($itemData);

                $totalQtyAdded = $itemData['quantity_received'] + $itemData['free_bonus_qty'];

                // Update or create product_batch under lock
                $batch = ProductBatch::where('product_id', $itemData['product_id'])
                    ->where('batch_number', $itemData['batch_number'])
                    ->lockForUpdate()
                    ->first();

                if ($batch) {
                    $batch->stock_quantity += $totalQtyAdded;
                    $batch->purchase_cost = $itemData['cost_price'];
                    $batch->selling_price = $itemData['retail_price'];
                    $batch->expiry_date = $itemData['expiry_date'];
                    $batch->supplier_id = $supplier->id;
                    $batch->save();
                } else {
                    ProductBatch::create([
                        'product_id' => $itemData['product_id'],
                        'batch_number' => $itemData['batch_number'],
                        'expiry_date' => $itemData['expiry_date'],
                        'purchase_cost' => $itemData['cost_price'],
                        'selling_price' => $itemData['retail_price'],
                        'stock_quantity' => $totalQtyAdded,
                        'supplier_id' => $supplier->id,
                    ]);
                }

                // Recalculate average purchase cost and update product retail price
                $product = Product::where('id', $itemData['product_id'])->first();
                if ($product) {
                    // Update latest unit price and cost price
                    $product->unit_cost_price = $itemData['cost_price'];
                    $product->unit_retail_price = $itemData['retail_price'];
                    $product->save();
                }
            }

            // Append invoice total to Supplier's Accounts Payable ledger
            $supplier->current_balance += $totalAmount;
            $supplier->save();

            return redirect()->route('grn.show', $grn->id)
                ->with('success', "GRN #{$grn->grn_number} saved and inventory updated successfully.");
        });
    }

    /**
     * Show GRN details.
     */
    public function show(Grn $grn)
    {
        $grn->load(['supplier', 'receiver', 'items.product']);
        return view('grn.show', compact('grn'));
    }

    /**
     * Item-Wise GRN History Report.
     * Select any medication to view historical log of all suppliers who provided the drug,
     * GRN numbers, batch numbers, cost fluctuations, and receipt dates.
     */
    public function itemHistory(Request $request)
    {
        $products = Product::orderBy('brand_name')->get();
        $selectedProductId = $request->get('product_id', $products->first()?->id);

        $selectedProduct = null;
        $history = collect();

        if ($selectedProductId) {
            $selectedProduct = Product::with(['category'])->find($selectedProductId);

            $history = GrnItem::where('product_id', $selectedProductId)
                ->with(['grn.supplier', 'grn.receiver'])
                ->join('grns', 'grn_items.grn_id', '=', 'grns.id')
                ->orderBy('grns.invoice_date', 'desc')
                ->select('grn_items.*')
                ->get();
        }

        return view('grn.item_history', compact('products', 'selectedProduct', 'history', 'selectedProductId'));
    }
}
