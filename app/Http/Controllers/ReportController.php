<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerExtraPayment;
use App\Models\CustomerSettlement;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Reports Dashboard Hub.
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * 1. Customer vs Transaction
     * Drill down into sales invoices and payment modes per customer.
     */
    public function customerVsTransaction(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $selectedCustomerId = $request->get('customer_id');

        $query = Sale::with(['customer', 'cashier', 'items.product'])->latest();

        if ($selectedCustomerId) {
            $query->where('customer_id', $selectedCustomerId);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        $sales = $query->paginate(20);

        $summary = [
            'total_sales' => (float) (clone $query)->sum('grand_total'),
            'total_paid' => (float) (clone $query)->sum('amount_paid'),
            'total_due' => (float) (clone $query)->sum('balance_due'),
            'count' => (clone $query)->count(),
        ];

        return view('reports.customer_vs_transaction', compact('sales', 'customers', 'selectedCustomerId', 'summary'));
    }

    /**
     * 2. Supplier vs Transaction
     * Comprehensive view of GRNs, purchase orders, and payment records.
     */
    public function supplierVsTransaction(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $selectedSupplierId = $request->get('supplier_id');

        $grnQuery = Grn::with(['supplier', 'receiver'])->latest();
        $paymentQuery = SupplierPayment::with(['supplier', 'grn', 'recorder'])->latest();

        if ($selectedSupplierId) {
            $grnQuery->where('supplier_id', $selectedSupplierId);
            $paymentQuery->where('supplier_id', $selectedSupplierId);
        }

        if ($request->filled('start_date')) {
            $grnQuery->whereDate('invoice_date', '>=', $request->start_date);
            $paymentQuery->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $grnQuery->whereDate('invoice_date', '<=', $request->end_date);
            $paymentQuery->whereDate('payment_date', '<=', $request->end_date);
        }

        $grns = $grnQuery->paginate(15, ['*'], 'grn_page');
        $payments = $paymentQuery->paginate(15, ['*'], 'payment_page');

        $selectedSupplier = $selectedSupplierId ? Supplier::find($selectedSupplierId) : null;

        return view('reports.supplier_vs_transaction', compact('suppliers', 'selectedSupplier', 'selectedSupplierId', 'grns', 'payments'));
    }

    /**
     * 3. Customer A/R Aging
     * Accounts Receivable breakdown (0-30, 31-60, 61-90, 90+ days overdue).
     */
    public function customerAging(Request $request)
    {
        $agingData = $this->ledgerService->getCustomerAging();
        return view('reports.customer_aging', compact('agingData'));
    }

    /**
     * 4. Customer Balance Summary
     * Consolidated balances of all registered debtor accounts.
     */
    public function customerBalanceSummary(Request $request)
    {
        $query = Customer::orderBy('name');

        if ($request->get('filter') === 'debt_only') {
            $query->where('current_debt_balance', '>', 0);
        }

        $customers = $query->paginate(25);
        $totalDebt = Customer::sum('current_debt_balance');
        $totalLimit = Customer::sum('credit_limit');

        return view('reports.customer_balance_summary', compact('customers', 'totalDebt', 'totalLimit'));
    }

    /**
     * 5. Sales by Customer
     * Revenue contribution rankings and transaction volumes.
     */
    public function salesByCustomer(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $customerSales = Customer::select('customers.id', 'customers.name', 'customers.phone', 'customers.current_debt_balance')
            ->join('sales', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.status', 'completed')
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$startDate, $endDate])
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.current_debt_balance')
            ->selectRaw('COUNT(sales.id) as total_invoices')
            ->selectRaw('SUM(sales.grand_total) as total_revenue')
            ->selectRaw('AVG(sales.grand_total) as avg_order_value')
            ->orderByDesc('total_revenue')
            ->get();

        $walkinSales = Sale::whereNull('customer_id')
            ->where('status', 'completed')
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->selectRaw('COUNT(id) as total_invoices')
            ->selectRaw('SUM(grand_total) as total_revenue')
            ->first();

        return view('reports.sales_by_customer', compact('customerSales', 'walkinSales', 'startDate', 'endDate'));
    }

    /**
     * 6. Customer Extra Payments
     * Track advance deposits and unallocated credit balances.
     */
    public function customerExtraPayments(Request $request)
    {
        $query = CustomerExtraPayment::with('customer')->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $extraPayments = $query->paginate(20);
        $totalExtra = (clone $query)->sum('amount');

        return view('reports.customer_extra_payments', compact('extraPayments', 'totalExtra'));
    }

    /**
     * 7. Patient Rx History
     * Regulatory tracking of prescription-only and controlled drug sales by patient/doctor.
     */
    public function patientRxHistory(Request $request)
    {
        $query = Prescription::with(['sale.customer', 'sale.cashier', 'sale.items.product'])
            ->latest();

        if ($request->filled('doctor_name')) {
            $query->where('doctor_name', 'like', "%{$request->doctor_name}%");
        }

        if ($request->filled('patient_name')) {
            $query->where('patient_name', 'like', "%{$request->patient_name}%");
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $prescriptions = $query->paginate(20);

        return view('reports.patient_rx_history', compact('prescriptions'));
    }

    /**
     * 8. Inventory Valuation
     * Real-time stock asset value calculated using purchase cost vs. projected retail revenue.
     */
    public function inventoryValuation(Request $request)
    {
        $batches = ProductBatch::with(['product.category', 'supplier'])
            ->where('stock_quantity', '>', 0)
            ->get();

        $totalCostValuation = 0.0;
        $totalRetailValuation = 0.0;
        $totalStockUnits = 0;

        foreach ($batches as $batch) {
            $qty = $batch->stock_quantity;
            $totalStockUnits += $qty;
            $totalCostValuation += round($qty * (float) $batch->purchase_cost, 2);
            $totalRetailValuation += round($qty * (float) $batch->selling_price, 2);
        }

        $projectedMargin = $totalRetailValuation - $totalCostValuation;
        $projectedMarginPercent = $totalRetailValuation > 0 ? ($projectedMargin / $totalRetailValuation) * 100 : 0;

        return view('reports.inventory_valuation', compact(
            'batches',
            'totalCostValuation',
            'totalRetailValuation',
            'totalStockUnits',
            'projectedMargin',
            'projectedMarginPercent'
        ));
    }

    /**
     * 9. FEFO Expiry Alerts
     * Filterable dashboard highlighting batches: Expired (Red), Critical <= 30 days (Orange), and Near Expiry <= 90 days (Yellow).
     */
    public function expiryAlerts(Request $request)
    {
        $today = now()->toDateString();
        $criticalLimit = now()->addDays(30)->toDateString();
        $nearLimit = now()->addDays(90)->toDateString();

        $filter = $request->get('filter', 'all_alerts'); // all_alerts, expired, critical, near

        $query = ProductBatch::with(['product.category', 'supplier'])
            ->where('stock_quantity', '>', 0);

        if ($filter === 'expired') {
            $query->where('expiry_date', '<=', $today);
        } elseif ($filter === 'critical') {
            $query->where('expiry_date', '>', $today)->where('expiry_date', '<=', $criticalLimit);
        } elseif ($filter === 'near') {
            $query->where('expiry_date', '>', $criticalLimit)->where('expiry_date', '<=', $nearLimit);
        } else {
            // all alerts: <= 90 days
            $query->where('expiry_date', '<=', $nearLimit);
        }

        $batches = $query->orderBy('expiry_date', 'asc')->paginate(25);

        $counts = [
            'expired' => ProductBatch::where('stock_quantity', '>', 0)->where('expiry_date', '<=', $today)->count(),
            'critical' => ProductBatch::where('stock_quantity', '>', 0)->where('expiry_date', '>', $today)->where('expiry_date', '<=', $criticalLimit)->count(),
            'near' => ProductBatch::where('stock_quantity', '>', 0)->where('expiry_date', '>', $criticalLimit)->where('expiry_date', '<=', $nearLimit)->count(),
        ];

        return view('reports.expiry_alerts', compact('batches', 'counts', 'filter'));
    }

    /**
     * 10. Profit & Loss Statement
     * Net margin analysis factoring in sales revenue, cost of goods sold (COGS), discounts, and stock write-offs.
     */
    public function profitLoss(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Sales Revenue
        $completedSales = Sale::where('status', 'completed')
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->get();

        $grossSales = (float) $completedSales->sum('subtotal');
        $totalDiscounts = (float) $completedSales->sum('discount_amount');
        $totalTax = (float) $completedSales->sum('tax_amount');
        $netSalesRevenue = (float) $completedSales->sum('grand_total');

        // Cost of Goods Sold (COGS)
        $saleIds = $completedSales->pluck('id');
        $saleItems = SaleItem::whereIn('sale_id', $saleIds)->get();

        $totalCogs = (float) $saleItems->sum(function ($item) {
            return $item->quantity * $item->cost_price;
        });

        // Stock Write-Offs (Damage, Expiry, Breakage)
        $writeOffs = StockAdjustment::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->whereIn('reason', ['Damage', 'Expiry', 'Breakage'])
            ->with('batch')
            ->get();

        $totalWriteOffLoss = (float) $writeOffs->sum(function ($adj) {
            $lossQty = max(0, $adj->previous_quantity - $adj->new_quantity);
            $cost = $adj->batch ? (float) $adj->batch->purchase_cost : 0.0;
            return $lossQty * $cost;
        });

        $grossProfit = $netSalesRevenue - $totalCogs;
        $netMargin = $grossProfit - $totalWriteOffLoss;
        $netMarginPercent = $netSalesRevenue > 0 ? ($netMargin / $netSalesRevenue) * 100 : 0;

        return view('reports.profit_loss', compact(
            'startDate',
            'endDate',
            'grossSales',
            'totalDiscounts',
            'totalTax',
            'netSalesRevenue',
            'totalCogs',
            'totalWriteOffLoss',
            'grossProfit',
            'netMargin',
            'netMarginPercent',
            'writeOffs'
        ));
    }

    /**
     * 11. EOD Z-Report
     * End-of-day register closure report reconciling system cash, card receipts,
     * credit sales, cash collections, and expected drawer totals.
     */
    public function eodZReport(Request $request)
    {
        $reportDate = $request->get('date', now()->toDateString());

        $sales = Sale::whereDate('created_at', $reportDate)
            ->where('status', 'completed')
            ->get();

        $cashSales = (float) $sales->where('payment_type', 'cash')->sum('amount_paid');
        $cardSales = (float) $sales->where('payment_type', 'card')->sum('amount_paid');
        $qrSales = (float) $sales->where('payment_type', 'qr')->sum('amount_paid');
        $creditSales = (float) $sales->where('payment_type', 'credit_lending')->sum('grand_total');
        $splitSalesCash = (float) $sales->where('payment_type', 'split')->sum('amount_paid');

        // Customer Debt Collections (Cash received at counter for past debts)
        $debtCollectionsCash = (float) CustomerSettlement::whereDate('date', $reportDate)
            ->where('payment_method', 'Cash')
            ->sum('amount_received');

        $debtCollectionsOther = (float) CustomerSettlement::whereDate('date', $reportDate)
            ->where('payment_method', '!=', 'Cash')
            ->sum('amount_received');

        $totalRegisterCash = $cashSales + $splitSalesCash + $debtCollectionsCash;
        $totalSystemGross = (float) $sales->sum('grand_total');

        $cashiers = Sale::whereDate('created_at', $reportDate)
            ->with('cashier')
            ->select('cashier_id', DB::raw('COUNT(*) as total_bills'), DB::raw('SUM(grand_total) as cashier_revenue'))
            ->groupBy('cashier_id')
            ->get();

        return view('reports.eod_z_report', compact(
            'reportDate',
            'sales',
            'cashSales',
            'cardSales',
            'qrSales',
            'creditSales',
            'splitSalesCash',
            'debtCollectionsCash',
            'debtCollectionsOther',
            'totalRegisterCash',
            'totalSystemGross',
            'cashiers'
        ));
    }

    /**
     * 12. Item Stock Movement & Date-Range Comparison Report
     * Displays initial stock on start_date, movements (GRN in, Sales out, Returns, Adjustments),
     * closing stock on end_date, and stock difference, with Category/Item filtering and PDF/Excel export.
     */
    public function itemStockMovement(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $categoryId = $request->get('category_id');
        $search = trim($request->get('q', ''));
        $selectedProductIds = array_filter((array) $request->get('product_ids', []), fn($v) => is_numeric($v));

        $categories = Category::orderBy('name')->get();
        $allCatalogProducts = Product::with('category')->orderBy('brand_name')->get();

        $productQuery = Product::with(['category', 'batches']);

        if (!empty($selectedProductIds)) {
            $productQuery->whereIn('id', $selectedProductIds);
        } else {
            if (!empty($categoryId)) {
                $productQuery->where('category_id', $categoryId);
            }
            if (!empty($search)) {
                $productQuery->search($search);
            }
        }

        $products = $productQuery->orderBy('brand_name')->get();
        $pIds = $products->pluck('id');

        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        // 1. Movements WITHIN [startDate, endDate]
        $grnInPeriod = GrnItem::whereIn('product_id', $pIds)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->select('product_id', DB::raw('SUM(quantity_received + free_bonus_qty) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $soldInPeriod = SaleItem::whereIn('product_id', $pIds)
            ->whereHas('sale', fn($q) => $q->where('status', 'completed')->whereBetween('created_at', [$startDateTime, $endDateTime]))
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $custRetInPeriod = SaleReturnItem::whereIn('product_id', $pIds)
            ->where('restock_status', 'restocked')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->select('product_id', DB::raw('SUM(quantity_returned) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $suppRetInPeriod = PurchaseReturnItem::whereIn('product_id', $pIds)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->select('product_id', DB::raw('SUM(quantity_returned) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $adjInPeriod = StockAdjustment::whereIn('product_id', $pIds)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->select('product_id', DB::raw('SUM(new_quantity - previous_quantity) as delta'))
            ->groupBy('product_id')
            ->pluck('delta', 'product_id');

        // 2. Movements AFTER endDate (to reconstruct exact stock on endDate)
        $grnAfter = GrnItem::whereIn('product_id', $pIds)
            ->where('created_at', '>', $endDateTime)
            ->select('product_id', DB::raw('SUM(quantity_received + free_bonus_qty) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $soldAfter = SaleItem::whereIn('product_id', $pIds)
            ->whereHas('sale', fn($q) => $q->where('status', 'completed')->where('created_at', '>', $endDateTime))
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $custRetAfter = SaleReturnItem::whereIn('product_id', $pIds)
            ->where('restock_status', 'restocked')
            ->where('created_at', '>', $endDateTime)
            ->select('product_id', DB::raw('SUM(quantity_returned) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $suppRetAfter = PurchaseReturnItem::whereIn('product_id', $pIds)
            ->where('created_at', '>', $endDateTime)
            ->select('product_id', DB::raw('SUM(quantity_returned) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $adjAfter = StockAdjustment::whereIn('product_id', $pIds)
            ->where('created_at', '>', $endDateTime)
            ->select('product_id', DB::raw('SUM(new_quantity - previous_quantity) as delta'))
            ->groupBy('product_id')
            ->pluck('delta', 'product_id');

        $reportRows = $products->map(function (Product $p) use (
            $grnInPeriod, $soldInPeriod, $custRetInPeriod, $suppRetInPeriod, $adjInPeriod,
            $grnAfter, $soldAfter, $custRetAfter, $suppRetAfter, $adjAfter
        ) {
            $currentLiveStock = (int) $p->batches->sum('stock_quantity');

            $afterNetDelta = (int) ($grnAfter[$p->id] ?? 0)
                - (int) ($soldAfter[$p->id] ?? 0)
                + (int) ($custRetAfter[$p->id] ?? 0)
                - (int) ($suppRetAfter[$p->id] ?? 0)
                + (int) ($adjAfter[$p->id] ?? 0);

            // Stock at end of endDate
            $endStock = $currentLiveStock - $afterNetDelta;

            // Movements during [startDate, endDate]
            $grnAdded = (int) ($grnInPeriod[$p->id] ?? 0);
            $soldOut = (int) ($soldInPeriod[$p->id] ?? 0);
            $custReturned = (int) ($custRetInPeriod[$p->id] ?? 0);
            $suppReturned = (int) ($suppRetInPeriod[$p->id] ?? 0);
            $adjDelta = (int) ($adjInPeriod[$p->id] ?? 0);

            $netDifference = $grnAdded - $soldOut + $custReturned - $suppReturned + $adjDelta;

            // Initial / Opening Stock at start of startDate
            $startStock = $endStock - $netDifference;

            $unitCost = (float) $p->unit_cost_price;
            $unitRetail = (float) $p->unit_retail_price;
            $unitsPerPack = max(1, (int) ($p->units_per_pack ?? 1));

            return [
                'id' => $p->id,
                'barcode_sku' => $p->barcode_sku,
                'brand_name' => $p->brand_name,
                'generic_name' => $p->generic_name ?? '—',
                'category_name' => $p->category?->name ?? 'General',
                'base_uom' => $p->base_uom ?? 'Unit',
                'units_per_pack' => $unitsPerPack,
                'unit_cost' => $unitCost,
                'unit_retail' => $unitRetail,
                'start_stock' => $startStock,
                'grn_in' => $grnAdded,
                'sold_out' => $soldOut,
                'returns_adj_net' => ($custReturned - $suppReturned + $adjDelta),
                'end_stock' => $endStock,
                'current_live_stock' => $currentLiveStock,
                'stock_difference' => $netDifference,
                'start_value_cost' => round($startStock * $unitCost, 2),
                'end_value_cost' => round($endStock * $unitCost, 2),
                'diff_value_cost' => round($netDifference * $unitCost, 2),
            ];
        });

        // Server-side CSV/Excel stream download if requested
        if ($request->get('export') === 'csv') {
            $filename = "Item_Stock_Movement_{$startDate}_to_{$endDate}.csv";
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($reportRows, $startDate, $endDate) {
                $out = fopen('php://output', 'w');
                // UTF-8 BOM for Excel compatibility
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [
                    'SKU / Barcode',
                    'Item / Medication Name',
                    'Category',
                    'UOM',
                    'Pack Size',
                    "Initial Stock ({$startDate})",
                    'GRN Received (+)',
                    'POS Sold (-)',
                    'Returns & Adjustments (+/-)',
                    "Closing Stock ({$endDate})",
                    'Stock Difference',
                    'Unit Cost (Rs.)',
                    'Retail Price (Rs.)',
                    "Closing Stock Value (Rs.)",
                ]);

                foreach ($reportRows as $r) {
                    fputcsv($out, [
                        $r['barcode_sku'],
                        $r['brand_name'],
                        $r['category_name'],
                        $r['base_uom'],
                        $r['units_per_pack'],
                        $r['start_stock'],
                        $r['grn_in'],
                        $r['sold_out'],
                        $r['returns_adj_net'],
                        $r['end_stock'],
                        ($r['stock_difference'] > 0 ? '+' : '') . $r['stock_difference'],
                        number_format($r['unit_cost'], 2, '.', ''),
                        number_format($r['unit_retail'], 2, '.', ''),
                        number_format($r['end_value_cost'], 2, '.', ''),
                    ]);
                }
                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        }

        $summary = [
            'items_count' => $reportRows->count(),
            'total_start_stock' => (int) $reportRows->sum('start_stock'),
            'total_grn_in' => (int) $reportRows->sum('grn_in'),
            'total_sold_out' => (int) $reportRows->sum('sold_out'),
            'total_returns_adj' => (int) $reportRows->sum('returns_adj_net'),
            'total_end_stock' => (int) $reportRows->sum('end_stock'),
            'total_difference' => (int) $reportRows->sum('stock_difference'),
            'total_start_value' => (float) $reportRows->sum('start_value_cost'),
            'total_end_value' => (float) $reportRows->sum('end_value_cost'),
        ];

        return view('reports.item_stock_movement', compact(
            'reportRows',
            'summary',
            'categories',
            'allCatalogProducts',
            'startDate',
            'endDate',
            'categoryId',
            'search',
            'selectedProductIds'
        ));
    }

    /**
     * 13. Item vs Transaction Report (Drug-Wise Sales Invoices & GRN Procurement Report)
     * View how a selected drug/item has been sold in a given date period (with sold quantities,
     * unit selling prices, sold amounts, and instant Invoice preview/reprint) alongside how it was
     * received via GRN (supplier, batch, cost/retail price, and instant GRN voucher preview) on the same page.
     */
    public function itemVsTransaction(Request $request)
    {
        $products = Product::with(['category', 'batches'])->orderBy('brand_name')->get();

        // Default to the most recently sold product if product_id parameter wasn't explicitly passed
        if ($request->has('product_id')) {
            $selectedProductId = $request->get('product_id');
        } else {
            $latestSoldItem = SaleItem::whereHas('sale', fn($q) => $q->where('status', 'completed'))
                ->latest()
                ->first();
            $selectedProductId = $latestSoldItem ? $latestSoldItem->product_id : $products->first()?->id;
        }

        $selectedProduct = $selectedProductId ? $products->firstWhere('id', (int) $selectedProductId) : null;

        // Date range handling (supports empty for All Time, or start_date & end_date)
        $preset = $request->get('preset', '');
        if ($preset === 'today') {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($preset === 'yesterday') {
            $startDate = now()->subDay()->toDateString();
            $endDate = now()->subDay()->toDateString();
        } elseif ($preset === 'last_7_days') {
            $startDate = now()->subDays(6)->toDateString();
            $endDate = now()->toDateString();
        } elseif ($preset === 'this_month') {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($preset === 'all_time') {
            $startDate = '';
            $endDate = '';
        } else {
            $startDate = $request->has('start_date')
                ? (string) $request->get('start_date')
                : now()->startOfMonth()->toDateString();
            $endDate = $request->has('end_date')
                ? (string) $request->get('end_date')
                : now()->toDateString();
        }

        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $grnScope = $request->get('grn_scope', 'auto'); // 'auto', 'period', 'all'

        // 1. Query Sold Line Items (POS Sales)
        $saleItemsQuery = SaleItem::with([
                'sale.customer',
                'sale.cashier',
                'sale.items.product',
                'product',
                'batch',
            ])
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed');
                if ($startDate !== '') {
                    $q->whereDate('created_at', '>=', $startDate);
                }
                if ($endDate !== '') {
                    $q->whereDate('created_at', '<=', $endDate);
                }
            })
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->orderBy('sales.created_at', 'desc')
            ->select('sale_items.*');

        if ($selectedProductId) {
            $saleItemsQuery->where('sale_items.product_id', $selectedProductId);
        }

        $allMatchingSaleItems = (clone $saleItemsQuery)->get();
        $saleItems = $saleItemsQuery->paginate(20, ['*'], 'sales_page');

        $salesSummary = [
            'total_qty_sold' => (int) $allMatchingSaleItems->sum('quantity'),
            'total_sold_amount' => (float) $allMatchingSaleItems->sum('subtotal'),
            'total_cogs' => (float) $allMatchingSaleItems->sum(fn($i) => $i->quantity * (float) $i->cost_price),
            'invoices_count' => $allMatchingSaleItems->pluck('sale_id')->unique()->count(),
            'lines_count' => $allMatchingSaleItems->count(),
        ];
        $salesSummary['gross_profit'] = $salesSummary['total_sold_amount'] - $salesSummary['total_cogs'];

        // 2. Query GRN Consignment Line Items (How item was received)
        $baseGrnQuery = GrnItem::with([
                'grn.supplier',
                'grn.receiver',
                'grn.items.product',
                'product',
            ])
            ->join('grns', 'grn_items.grn_id', '=', 'grns.id')
            ->orderBy('grns.invoice_date', 'desc')
            ->orderBy('grn_items.id', 'desc')
            ->select('grn_items.*');

        if ($selectedProductId) {
            $baseGrnQuery->where('grn_items.product_id', $selectedProductId);
        }

        $periodGrnQuery = clone $baseGrnQuery;
        if ($startDate !== '') {
            $periodGrnQuery->where(function ($q) use ($startDate) {
                $q->whereDate('grns.invoice_date', '>=', $startDate)
                  ->orWhereDate('grn_items.created_at', '>=', $startDate);
            });
        }
        if ($endDate !== '') {
            $periodGrnQuery->where(function ($q) use ($endDate) {
                $q->whereDate('grns.invoice_date', '<=', $endDate)
                  ->orWhereDate('grn_items.created_at', '<=', $endDate);
            });
        }

        $periodGrnCount = (clone $periodGrnQuery)->count();
        $showingHistoricalGrnsFallback = false;

        if ($grnScope === 'all') {
            $activeGrnQuery = $baseGrnQuery;
        } elseif ($grnScope === 'period') {
            $activeGrnQuery = $periodGrnQuery;
        } else {
            // 'auto': if GRNs exist in the selected period, show period GRNs;
            // if 0 GRNs in that narrow period (e.g., Yesterday) and an item is selected, fallback to showing all historical GRNs for that item
            if ($periodGrnCount > 0 || !$selectedProductId) {
                $activeGrnQuery = $periodGrnQuery;
            } else {
                $activeGrnQuery = $baseGrnQuery;
                $showingHistoricalGrnsFallback = ($startDate !== '' || $endDate !== '');
            }
        }

        $allMatchingGrnItems = (clone $activeGrnQuery)->get();
        $grnItems = $activeGrnQuery->paginate(20, ['*'], 'grn_page');

        $grnSummary = [
            'total_qty_received' => (int) $allMatchingGrnItems->sum('quantity_received'),
            'total_bonus_received' => (int) $allMatchingGrnItems->sum('free_bonus_qty'),
            'total_units_added' => (int) $allMatchingGrnItems->sum(fn($g) => $g->quantity_received + $g->free_bonus_qty),
            'total_grn_amount' => (float) $allMatchingGrnItems->sum('subtotal'),
            'grns_count' => $allMatchingGrnItems->pluck('grn_id')->unique()->count(),
            'period_grn_count' => $periodGrnCount,
        ];

        return view('reports.item_vs_transaction', compact(
            'products',
            'selectedProduct',
            'selectedProductId',
            'startDate',
            'endDate',
            'preset',
            'grnScope',
            'showingHistoricalGrnsFallback',
            'saleItems',
            'salesSummary',
            'grnItems',
            'grnSummary'
        ));
    }
}
