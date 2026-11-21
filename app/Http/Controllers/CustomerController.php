<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerExtraPayment;
use App\Models\CustomerSettlement;
use App\Models\Sale;
use App\Services\LedgerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * List all customers with balances and quick search.
     */
    public function index(Request $request)
    {
        $query = Customer::latest();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('nic_number', 'like', "%{$term}%");
            });
        }

        if ($request->get('filter') === 'with_debt') {
            $query->where('current_debt_balance', '>', 0);
        }

        $customers = $query->paginate(20);
        $totalReceivable = Customer::sum('current_debt_balance');

        return view('customers.index', compact('customers', 'totalReceivable'));
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()?->tenant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => [
                'required',
                'string',
                'max:20',
                \Illuminate\Validation\Rule::unique('customers', 'phone')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'nic_number' => 'nullable|string|max:30',
            'credit_limit' => 'required|numeric|min:0',
            'opening_debt_balance' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'nic_number' => $validated['nic_number'] ?? null,
            'credit_limit' => $validated['credit_limit'],
            'current_debt_balance' => (float) ($validated['opening_debt_balance'] ?? 0),
            'address' => $validated['address'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer registered successfully.',
                'customer' => $customer,
            ]);
        }

        return redirect()->route('customers.index')->with('success', "Customer '{$customer->name}' created.");
    }

    /**
     * View customer profile, ledger overview, sales audit history, settlements, and aging.
     */
    public function show(Customer $customer, Request $request)
    {
        $salesQuery = Sale::where('customer_id', $customer->id)
            ->with(['items.product', 'cashier'])
            ->latest();

        if ($request->filled('start_date')) {
            $salesQuery->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $salesQuery->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('payment_type')) {
            $salesQuery->where('payment_type', $request->payment_type);
        }

        $sales = $salesQuery->paginate(15);

        $settlements = CustomerSettlement::where('customer_id', $customer->id)
            ->with('receiver')
            ->latest()
            ->limit(20)
            ->get();

        $extraPayments = CustomerExtraPayment::where('customer_id', $customer->id)
            ->latest()
            ->limit(10)
            ->get();

        $agingData = $this->ledgerService->getCustomerAging($customer->id);

        $summary = [
            'total_sales_count' => Sale::where('customer_id', $customer->id)->count(),
            'total_purchased' => (float) Sale::where('customer_id', $customer->id)->sum('grand_total'),
            'total_lent_outstanding' => (float) $customer->current_debt_balance,
            'credit_limit' => (float) $customer->credit_limit,
            'remaining_credit' => (float) $customer->remaining_credit,
            'total_settled' => (float) CustomerSettlement::where('customer_id', $customer->id)->sum('amount_received'),
        ];

        return view('customers.show', compact('customer', 'sales', 'settlements', 'extraPayments', 'agingData', 'summary'));
    }

    /**
     * Update customer details.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'nic_number' => 'nullable|string|max:30',
            'credit_limit' => 'required|numeric|min:0',
            'address' => 'nullable|string',
        ]);

        $customer->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Customer '{$customer->name}' details updated successfully.",
                'customer' => $customer->fresh(),
            ]);
        }

        return back()->with('success', "Customer '{$customer->name}' details updated successfully.");
    }

    /**
     * Process debt settlement payment against outstanding balance.
     */
    public function settle(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount_received' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Card,Bank Transfer,QR',
            'notes' => 'nullable|string',
        ]);

        try {
            $userId = Auth::id() ?? 1;
            $settlement = $this->ledgerService->settleCustomerDebt(
                $customer->id,
                (float) $validated['amount_received'],
                $validated['payment_method'],
                $userId,
                $validated['notes'] ?? null
            );

            return back()->with('success', "Settlement of Rs. " . number_format($settlement->amount_received, 2) . " received. Updated debt balance: Rs. " . number_format($customer->fresh()->current_debt_balance, 2));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record advance or extra payment from customer.
     */
    public function extraPayment(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $extra = $this->ledgerService->recordCustomerExtraPayment(
                $customer->id,
                (float) $validated['amount'],
                $validated['payment_method'],
                $validated['notes'] ?? null
            );

            return back()->with('success', "Advance deposit of Rs. " . number_format($extra->amount, 2) . " logged successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin adjustment of customer debt balance (for migrating past balances or manual audit corrections).
     */
    public function adjustBalance(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'adjustment_mode' => 'required|in:set_exact,increase_debt,decrease_debt',
            'amount' => 'required|numeric|min:0',
            'notes' => 'required|string|max:255',
        ]);

        $prevBalance = (float) $customer->current_debt_balance;
        $inputAmount = (float) $validated['amount'];

        if ($validated['adjustment_mode'] === 'set_exact') {
            $newBalance = max(0.0, $inputAmount);
        } elseif ($validated['adjustment_mode'] === 'increase_debt') {
            $newBalance = $prevBalance + $inputAmount;
        } else {
            $newBalance = max(0.0, $prevBalance - $inputAmount);
        }

        $customer->current_debt_balance = round($newBalance, 2);
        $customer->save();

        CustomerSettlement::create([
            'customer_id' => $customer->id,
            'amount_received' => round($newBalance - $prevBalance, 2),
            'payment_method' => 'Balance Adjustment',
            'notes' => "[Admin Balance Adjustment: Rs. " . number_format($prevBalance, 2) . " -> Rs. " . number_format($newBalance, 2) . "] " . $validated['notes'],
            'received_by' => Auth::id() ?? 1,
            'date' => now(),
        ]);

        return back()->with('success', "Customer '{$customer->name}' debt balance adjusted from Rs. " . number_format($prevBalance, 2) . " to Rs. " . number_format($newBalance, 2) . ".");
    }

    /**
     * Delete customer account (Admin).
     */
    public function destroy(Customer $customer)
    {
        $name = $customer->name;
        $customer->delete();

        return redirect()->route('customers.index')->with('success', "Customer '{$name}' deleted successfully.");
    }
}
