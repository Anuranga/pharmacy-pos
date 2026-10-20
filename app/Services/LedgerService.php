<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerExtraPayment;
use App\Models\CustomerSettlement;
use App\Models\Grn;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Settle customer debt with cash, card, bank transfer, or QR.
     * Decreases current_debt_balance and records settlement receipt.
     */
    public function settleCustomerDebt(
        int $customerId,
        float $amount,
        string $paymentMethod,
        int $receivedBy,
        ?string $notes = null
    ): CustomerSettlement {
        if ($amount <= 0) {
            throw new Exception("Settlement amount must be greater than zero.");
        }

        return DB::transaction(function () use ($customerId, $amount, $paymentMethod, $receivedBy, $notes) {
            $customer = Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();

            $newDebt = max(0.0, (float) $customer->current_debt_balance - $amount);
            $customer->current_debt_balance = $newDebt;
            $customer->save();

            // Also allocate payment against unpaid credit sales (FIFO order)
            $unpaidSales = Sale::where('customer_id', $customerId)
                ->where('payment_type', 'credit_lending')
                ->where('balance_due', '>', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $remainingSettlement = $amount;
            foreach ($unpaidSales as $sale) {
                if ($remainingSettlement <= 0) break;

                $payForSale = min((float) $sale->balance_due, $remainingSettlement);
                $sale->amount_paid += $payForSale;
                $sale->balance_due -= $payForSale;
                $sale->save();

                $remainingSettlement -= $payForSale;
            }

            // Create settlement record
            return CustomerSettlement::create([
                'customer_id' => $customerId,
                'amount_received' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'received_by' => $receivedBy,
                'date' => now(),
            ]);
        });
    }

    /**
     * Record advance or extra deposit from a customer.
     */
    public function recordCustomerExtraPayment(
        int $customerId,
        float $amount,
        string $paymentMethod = 'Cash',
        ?string $notes = null
    ): CustomerExtraPayment {
        if ($amount <= 0) {
            throw new Exception("Extra payment amount must be greater than zero.");
        }

        return DB::transaction(function () use ($customerId, $amount, $paymentMethod, $notes) {
            $customer = Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();

            // If customer has debt, offset it first
            if ($customer->current_debt_balance > 0) {
                $offset = min((float) $customer->current_debt_balance, $amount);
                $customer->current_debt_balance -= $offset;
                $customer->save();
            }

            return CustomerExtraPayment::create([
                'customer_id' => $customerId,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => $paymentMethod,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Make a payment to a supplier (Cash, Cheque, Bank Transfer).
     * Reduces supplier current_balance (Accounts Payable) and updates GRN paid amount if linked.
     */
    public function recordSupplierPayment(
        int $supplierId,
        float $amount,
        string $paymentMethod,
        ?string $referenceNumber = null,
        ?int $grnId = null,
        ?string $paymentDate = null,
        ?string $notes = null,
        ?int $recordedBy = null
    ): SupplierPayment {
        if ($amount <= 0) {
            throw new Exception("Payment amount must be greater than zero.");
        }

        return DB::transaction(function () use ($supplierId, $amount, $paymentMethod, $referenceNumber, $grnId, $paymentDate, $notes, $recordedBy) {
            $supplier = Supplier::where('id', $supplierId)->lockForUpdate()->firstOrFail();

            // Decrease accounts payable
            $supplier->current_balance = max(0.0, (float) $supplier->current_balance - $amount);
            $supplier->save();

            // If linked to a specific GRN, update paid_amount
            if ($grnId) {
                $grn = Grn::where('id', $grnId)->lockForUpdate()->first();
                if ($grn) {
                    $grn->paid_amount += $amount;
                    $grn->save();
                }
            }

            return SupplierPayment::create([
                'supplier_id' => $supplierId,
                'grn_id' => $grnId,
                'amount_paid' => $amount,
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'payment_date' => $paymentDate ?? now()->toDateString(),
                'notes' => $notes,
                'recorded_by' => $recordedBy,
            ]);
        });
    }

    /**
     * Calculate Accounts Receivable (A/R) Aging breakdown for a customer or all customers:
     * 0-30 days, 31-60 days, 61-90 days, 90+ days overdue.
     */
    public function getCustomerAging(?int $customerId = null): array
    {
        $query = Sale::where('payment_type', 'credit_lending')
            ->where('balance_due', '>', 0)
            ->with('customer');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $creditSales = $query->get();

        $aging = [
            'current' => 0.0, // 0-30 days
            'tier_30_60' => 0.0, // 31-60 days
            'tier_60_90' => 0.0, // 61-90 days
            'tier_90_plus' => 0.0, // 90+ days
            'total_receivable' => 0.0,
            'customers' => [],
        ];

        $now = Carbon::now();

        foreach ($creditSales as $sale) {
            $days = $now->diffInDays(Carbon::parse($sale->created_at));
            $due = (float) $sale->balance_due;
            $aging['total_receivable'] += $due;

            $custKey = $sale->customer_id ?? 0;
            if (!isset($aging['customers'][$custKey])) {
                $aging['customers'][$custKey] = [
                    'customer' => $sale->customer,
                    'current' => 0.0,
                    'tier_30_60' => 0.0,
                    'tier_60_90' => 0.0,
                    'tier_90_plus' => 0.0,
                    'total' => 0.0,
                ];
            }
            $aging['customers'][$custKey]['total'] += $due;

            if ($days <= 30) {
                $aging['current'] += $due;
                $aging['customers'][$custKey]['current'] += $due;
            } elseif ($days <= 60) {
                $aging['tier_30_60'] += $due;
                $aging['customers'][$custKey]['tier_30_60'] += $due;
            } elseif ($days <= 90) {
                $aging['tier_60_90'] += $due;
                $aging['customers'][$custKey]['tier_60_90'] += $due;
            } else {
                $aging['tier_90_plus'] += $due;
                $aging['customers'][$custKey]['tier_90_plus'] += $due;
            }
        }

        return $aging;
    }
}
