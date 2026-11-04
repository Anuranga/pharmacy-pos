<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerSettlement;
use App\Models\Grn;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Comprehensive Pharmacy Operations, Financial & Clinical Inventory Dashboard.
     */
    public function index()
    {
        $today = today();
        $yesterday = today()->subDay();
        $startOfMonth = now()->startOfMonth();
        $todayStr = $today->toDateString();
        $criticalLimit = now()->addDays(30)->toDateString();
        $nearLimit = now()->addDays(90)->toDateString();

        // 1. Today's Sales & Drawer Metrics
        $todaySales = Sale::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->get();

        $todayRevenue = (float) $todaySales->sum('grand_total');
        $todayBillsCount = $todaySales->count();
        $todayCashSales = (float) $todaySales->where('payment_type', 'cash')->sum('grand_total');
        $todayCardSales = (float) $todaySales->where('payment_type', 'card')->sum('grand_total');
        $todayQrSales = (float) $todaySales->where('payment_type', 'qr')->sum('grand_total');
        $todayCreditSales = (float) $todaySales->where('payment_type', 'credit_lending')->sum('grand_total');

        $todayDebtCashCollected = (float) CustomerSettlement::whereDate('date', $today)
            ->where('payment_method', 'Cash')
            ->sum('amount_received');

        $expectedDrawerCash = $todayCashSales + $todayDebtCashCollected;

        // Yesterday comparison
        $yesterdayRevenue = (float) Sale::whereDate('created_at', $yesterday)
            ->where('status', 'completed')
            ->sum('grand_total');

        $revenueGrowth = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : ($todayRevenue > 0 ? 100.0 : 0.0);

        // 2. Monthly Revenue, COGS & Gross Profit
        $monthSalesIds = Sale::where('status', 'completed')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->pluck('id');

        $monthRevenue = (float) Sale::whereIn('id', $monthSalesIds)->sum('grand_total');
        $monthBillsCount = $monthSalesIds->count();

        $monthCogs = (float) SaleItem::whereIn('sale_id', $monthSalesIds)
            ->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as total_cogs')
            ->value('total_cogs');

        $monthGrossProfit = $monthRevenue - $monthCogs;
        $monthMarginPercent = $monthRevenue > 0 ? round(($monthGrossProfit / $monthRevenue) * 100, 1) : 0.0;

        // 3. Receivables (Customer Lending) & Payables (Suppliers)
        $totalReceivables = (float) Customer::sum('current_debt_balance');
        $activeDebtorsCount = Customer::where('current_debt_balance', '>', 0)->count();
        $topDebtors = Customer::where('current_debt_balance', '>', 0)
            ->orderByDesc('current_debt_balance')
            ->limit(6)
            ->get();

        $totalPayables = (float) Supplier::sum('current_balance');
        $activeSuppliersCount = Supplier::where('current_balance', '>', 0)->count();

        // 4. Inventory Valuation & Stock Health
        $positiveBatches = ProductBatch::where('stock_quantity', '>', 0)->get();
        $inventoryCostValue = (float) $positiveBatches->sum(fn($b) => $b->stock_quantity * (float) $b->purchase_cost);
        $inventoryRetailValue = (float) $positiveBatches->sum(fn($b) => $b->stock_quantity * (float) $b->selling_price);
        $totalProductsCount = Product::count();

        // All products with summed batch stock
        $allProductsWithStock = Product::with('category')
            ->withSum('batches as total_stock_sum', 'stock_quantity')
            ->get()
            ->map(function ($p) {
                $p->total_stock_sum = (int) ($p->total_stock_sum ?? 0);
                return $p;
            });

        // Negative stock items (billed while out of stock)
        $negativeStockProducts = $allProductsWithStock
            ->filter(fn($p) => $p->total_stock_sum < 0)
            ->sortBy('total_stock_sum')
            ->take(10)
            ->values();

        // Low stock / Reorder alert items (where stock <= reorder_min)
        $lowStockProducts = $allProductsWithStock
            ->filter(fn($p) => $p->total_stock_sum <= (int) $p->reorder_min)
            ->sortBy('total_stock_sum')
            ->take(10)
            ->values();

        // 5. FEFO Expiry Alerts
        $expiryCounts = [
            'expired' => ProductBatch::where('stock_quantity', '>', 0)->where('expiry_date', '<=', $todayStr)->count(),
            'critical' => ProductBatch::where('stock_quantity', '>', 0)->where('expiry_date', '>', $todayStr)->where('expiry_date', '<=', $criticalLimit)->count(),
            'near' => ProductBatch::where('stock_quantity', '>', 0)->where('expiry_date', '>', $criticalLimit)->where('expiry_date', '<=', $nearLimit)->count(),
        ];

        $urgentExpiryBatches = ProductBatch::with('product')
            ->where('stock_quantity', '>', 0)
            ->where('expiry_date', '<=', $nearLimit)
            ->orderBy('expiry_date', 'asc')
            ->limit(8)
            ->get();

        // 6. 7-Day Revenue Trend Chart Data
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $daySales = Sale::whereDate('created_at', $date)->where('status', 'completed');
            $rev = (float) (clone $daySales)->sum('grand_total');
            $cnt = (clone $daySales)->count();
            $last7Days->push([
                'date' => $date->format('d M'),
                'day' => $date->format('D'),
                'revenue' => $rev,
                'bills' => $cnt,
            ]);
        }
        $maxDailyRevenue = max(1.0, (float) $last7Days->max('revenue'));

        // 7. Top Fast-Moving Products (by revenue/qty)
        $topSellingProducts = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty_sold'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->whereHas('sale', fn($q) => $q->where('status', 'completed'))
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit(6)
            ->get();

        // 8. Recent Sales & Recent GRNs
        $recentSales = Sale::with(['customer', 'cashier'])
            ->latest()
            ->limit(7)
            ->get();

        $recentGrns = Grn::with(['supplier', 'receiver'])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'todayRevenue',
            'todayBillsCount',
            'todayCashSales',
            'todayCardSales',
            'todayQrSales',
            'todayCreditSales',
            'todayDebtCashCollected',
            'expectedDrawerCash',
            'yesterdayRevenue',
            'revenueGrowth',
            'monthRevenue',
            'monthBillsCount',
            'monthCogs',
            'monthGrossProfit',
            'monthMarginPercent',
            'totalReceivables',
            'activeDebtorsCount',
            'topDebtors',
            'totalPayables',
            'activeSuppliersCount',
            'inventoryCostValue',
            'inventoryRetailValue',
            'totalProductsCount',
            'negativeStockProducts',
            'lowStockProducts',
            'expiryCounts',
            'urgentExpiryBatches',
            'last7Days',
            'maxDailyRevenue',
            'topSellingProducts',
            'recentSales',
            'recentGrns'
        ));
    }
}
