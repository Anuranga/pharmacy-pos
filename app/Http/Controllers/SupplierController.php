<?php

namespace App\Http\Controllers;

use App\Models\Grn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\LedgerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * List suppliers with total accounts payable liability.
     */
    public function index(Request $request)
    {
        $query = Supplier::latest();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('contact_person', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $suppliers = $query->paginate(15);
        $totalPayable = Supplier::sum('current_balance');

        return view('suppliers.index', compact('suppliers', 'totalPayable'));
    }

    /**
     * Store new supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'credit_period_days' => 'required|integer|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $supplier = Supplier::create([
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'credit_period_days' => $validated['credit_period_days'],
            'current_balance' => (float) ($validated['opening_balance'] ?? 0),
        ]);

        return redirect()->route('suppliers.index')->with('success', "Supplier '{$supplier->name}' added.");
    }

    /**
     * Supplier profile and running ledger.
     */
    public function show(Supplier $supplier)
    {
        $grns = Grn::where('supplier_id', $supplier->id)
            ->with('receiver')
            ->latest()
            ->paginate(10, ['*'], 'grn_page');

        $payments = SupplierPayment::where('supplier_id', $supplier->id)
            ->with(['grn', 'recorder'])
            ->latest()
            ->paginate(10, ['*'], 'payment_page');

        $pendingGrns = Grn::where('supplier_id', $supplier->id)
            ->whereRaw('paid_amount < total_amount')
            ->get();

        return view('suppliers.show', compact('supplier', 'grns', 'payments', 'pendingGrns'));
    }

    /**
     * Update supplier details.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'credit_period_days' => 'required|integer|min:0',
        ]);

        $supplier->update($validated);

        return back()->with('success', 'Supplier updated successfully.');
    }

    /**
     * Register a payment to supplier (Cash, Cheque, Bank Transfer).
     */
    public function pay(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:Cash,Cheque,Bank Transfer',
            'reference_number' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'grn_id' => 'nullable|exists:grns,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $payment = $this->ledgerService->recordSupplierPayment(
                $supplier->id,
                (float) $validated['amount_paid'],
                $validated['payment_method'],
                $validated['reference_number'] ?? null,
                $validated['grn_id'] ? (int) $validated['grn_id'] : null,
                $validated['payment_date'],
                $validated['notes'] ?? null,
                Auth::id() ?? 1
            );

            return back()->with('success', "Payment of Rs. " . number_format($payment->amount_paid, 2) . " registered to {$supplier->name}. Remaining liability: Rs. " . number_format($supplier->fresh()->current_balance, 2));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin adjustment of supplier payable balance (for migrating past balances or audit corrections).
     */
    public function adjustBalance(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'adjustment_mode' => 'required|in:set_exact,increase_payable,decrease_payable',
            'amount' => 'required|numeric|min:0',
            'notes' => 'required|string|max:255',
        ]);

        $prevBalance = (float) $supplier->current_balance;
        $inputAmount = (float) $validated['amount'];

        if ($validated['adjustment_mode'] === 'set_exact') {
            $newBalance = max(0.0, $inputAmount);
        } elseif ($validated['adjustment_mode'] === 'increase_payable') {
            $newBalance = $prevBalance + $inputAmount;
        } else {
            $newBalance = max(0.0, $prevBalance - $inputAmount);
        }

        $supplier->current_balance = round($newBalance, 2);
        $supplier->save();

        SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'grn_id' => null,
            'amount_paid' => round($newBalance - $prevBalance, 2),
            'payment_method' => 'Balance Adjustment',
            'reference_number' => 'ADJ-' . date('YmdHis'),
            'payment_date' => now()->toDateString(),
            'notes' => "[Admin Balance Adjustment: Rs. " . number_format($prevBalance, 2) . " -> Rs. " . number_format($newBalance, 2) . "] " . $validated['notes'],
            'recorded_by' => Auth::id() ?? 1,
        ]);

        return back()->with('success', "Supplier '{$supplier->name}' payable balance adjusted from Rs. " . number_format($prevBalance, 2) . " to Rs. " . number_format($newBalance, 2) . ".");
    }

    /**
     * Delete supplier account (Admin).
     */
    public function destroy(Supplier $supplier)
    {
        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', "Supplier '{$name}' deleted successfully.");
    }
}
