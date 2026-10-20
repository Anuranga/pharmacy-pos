<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerExtraPayment;
use App\Models\CustomerSettlement;
use App\Models\Grn;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
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
}
