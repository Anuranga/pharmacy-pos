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
        ]);

        $supplier = Supplier::create($validated);

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
}
