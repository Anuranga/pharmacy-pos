<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterDataController extends Controller
{
    protected StockAdjustmentService $adjustmentService;

    public function __construct(StockAdjustmentService $adjustmentService)
    {
        $this->adjustmentService = $adjustmentService;
    }

    /**
     * Master Data Hub & Product Catalog.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'batches'])->latest();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->search($term);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('item_type')) {
            $query->where('item_type', $request->item_type);
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $products = $query->paginate(20);
        $categories = Category::all();
        $totalProducts = Product::count();
        $totalBatches = ProductBatch::count();

        return view('master.index', compact('products', 'categories', 'totalProducts', 'totalBatches'));
    }

    /**
     * Show Product creation form.
     */
    public function createProduct()
    {
        $categories = Category::all();
        return view('master.product_create', compact('categories'));
    }

    /**
     * Store new drug in catalog.
     */
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'barcode_sku' => 'required|string|max:100|unique:products,barcode_sku',
            'brand_name' => 'required|string|max:150',
            'generic_name' => 'nullable|string|max:150',
            'dosage_strength' => 'nullable|string|max:50',
            'item_type' => 'required|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'base_uom' => 'required|string|max:30',
            'units_per_pack' => 'required|integer|min:1',
            'reorder_min' => 'required|integer|min:0',
            'reorder_max' => 'required|integer|min:0',
            'unit_cost_price' => 'required|numeric|min:0',
            'unit_retail_price' => 'required|numeric|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_controlled' => 'nullable|boolean',
        ]);

        $validated['requires_prescription'] = $request->boolean('requires_prescription');
        $validated['is_controlled'] = $request->boolean('is_controlled');

        $product = Product::create($validated);

        return redirect()->route('master.products.show', $product->id)
            ->with('success', "Drug '{$product->brand_name}' created successfully.");
    }

    /**
     * View drug details, active batches, and audit adjustments.
     */
    public function showProduct(Product $product)
    {
        $product->load(['category', 'batches.supplier', 'stockAdjustments.adjuster', 'saleItems' => function ($q) {
            $q->latest()->limit(10);
        }]);

        $categories = Category::all();

        return view('master.product_show', compact('product', 'categories'));
    }

    /**
     * Show Product edit form.
     */
    public function editProduct(Product $product)
    {
        $categories = Category::all();
        return view('master.product_edit', compact('product', 'categories'));
    }

    /**
     * Update product details.
     */
    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'barcode_sku' => 'required|string|max:100|unique:products,barcode_sku,' . $product->id,
            'brand_name' => 'required|string|max:150',
            'generic_name' => 'nullable|string|max:150',
            'dosage_strength' => 'nullable|string|max:50',
            'item_type' => 'required|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'base_uom' => 'required|string|max:30',
            'units_per_pack' => 'required|integer|min:1',
            'reorder_min' => 'required|integer|min:0',
            'reorder_max' => 'required|integer|min:0',
            'unit_cost_price' => 'required|numeric|min:0',
            'unit_retail_price' => 'required|numeric|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_controlled' => 'nullable|boolean',
        ]);

        $validated['requires_prescription'] = $request->boolean('requires_prescription');
        $validated['is_controlled'] = $request->boolean('is_controlled');

        $product->update($validated);

        return redirect()->route('master.products.show', $product->id)
            ->with('success', "Medication '{$product->brand_name}' updated successfully.");
    }

    /**
     * Delete product (soft delete).
     */
    public function destroyProduct(Product $product)
    {
        $name = $product->brand_name;
        $product->delete();

        return redirect()->route('master.index')->with('success', "Product '{$name}' archived.");
    }

    /**
     * Categories CRUD.
     */
    public function categories()
    {
        $categories = Category::withCount('products')->get();
        return view('master.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        Category::create($validated);

        return back()->with('success', 'Category added successfully.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Quick inline stock adjustment for Admins with mandatory reason logging.
     */
    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:product_batches,id',
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|in:Damage,Expiry,Breakage,Audit Recount,Other',
            'notes' => 'nullable|string',
        ]);

        try {
            $adjustment = $this->adjustmentService->adjustBatchQuantity(
                (int) $validated['batch_id'],
                (int) $validated['new_quantity'],
                $validated['reason'],
                Auth::id() ?? 1,
                $validated['notes'] ?? null
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Stock adjusted from {$adjustment->previous_quantity} to {$adjustment->new_quantity} ({$adjustment->reason}).",
                    'adjustment' => $adjustment,
                ]);
            }

            return back()->with('success', "Stock updated to {$adjustment->new_quantity} with reason '{$adjustment->reason}'.");
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
