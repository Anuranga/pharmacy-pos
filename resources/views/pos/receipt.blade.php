<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $sale->invoice_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
        }
        body {
            background-color: #fff;
            color: #000;
            font-size: 12px;
            line-height: 1.25;
            padding: 10px;
            width: 80mm;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .header {
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header p {
            font-size: 11px;
        }

        .meta-table, .items-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .meta-table td {
            font-size: 11px;
            padding: 1px 0;
        }
        
        .items-table {
            border-bottom: 1px dashed #000;
            margin-top: 4px;
            padding-bottom: 4px;
        }
        .items-table th {
            border-bottom: 1px dashed #000;
            padding: 4px 0;
            font-size: 11px;
        }
        .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        
        .totals-table td {
            padding: 2px 0;
            font-size: 12px;
        }
        .totals-table .grand-total td {
            border-top: 1px dashed #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .footer {
            margin-top: 8px;
            font-size: 10px;
            text-align: center;
        }

        .print-btn {
            background: #10b981;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            display: block;
            margin: 0 auto 10px auto;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print print-btn">🖨️ Print Receipt (ESC/POS)</button>

    <div class="header text-center">
        <h1>PHARMACARE PHARMACY</h1>
        <p>No. 45, Main Healthcare Boulevard</p>
        <p>Tel: +94 11 234 5678 | Reg: PH-2024-9981</p>
        <p class="font-bold" style="margin-top: 4px;">*** TAX / CASH INVOICE ***</p>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Invoice:</strong> {{ $sale->invoice_number }}</td>
            <td class="text-right"><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Cashier:</strong> {{ $sale->cashier->name ?? 'Staff' }}</td>
            <td class="text-right"><strong>Pay Mode:</strong> {{ strtoupper($sale->payment_type) }}</td>
        </tr>
        @if($sale->customer)
        <tr>
            <td colspan="2"><strong>Customer:</strong> {{ $sale->customer->name }} ({{ $sale->customer->phone }})</td>
        </tr>
        @endif
        @if($sale->prescription)
        <tr>
            <td colspan="2"><strong>Rx Doctor:</strong> {{ $sale->prescription->doctor_name }} ({{ $sale->prescription->doctor_reg_no ?? 'N/A' }})</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Patient:</strong> {{ $sale->prescription->patient_name }}</td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 50%;">Item / Batch</th>
                <th class="text-right" style="width: 15%;">Qty</th>
                <th class="text-right" style="width: 15%;">Price</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td class="text-left">
                    <div class="font-bold">{{ $item->product->brand_name }}</div>
                    <div style="font-size: 10px; color: #333;">B:{{ $item->batch->batch_number ?? 'N/A' }} Exp:{{ optional($item->batch->expiry_date)->format('m/y') }}</div>
                </td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right font-bold">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right font-mono">Rs. {{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        @if($sale->discount_amount > 0)
        <tr>
            <td>Discount:</td>
            <td class="text-right font-mono">- Rs. {{ number_format($sale->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>GRAND TOTAL:</td>
            <td class="text-right font-mono">Rs. {{ number_format($sale->grand_total, 2) }}</td>
        </tr>
        @if($sale->payment_type === 'cash')
        <tr>
            <td>Cash Tendered:</td>
            <td class="text-right font-mono">Rs. {{ number_format($sale->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td>Change Return:</td>
            <td class="text-right font-mono">Rs. {{ number_format(max(0, $sale->amount_paid - $sale->grand_total), 2) }}</td>
        </tr>
        @elseif($sale->payment_type === 'credit_lending')
        <tr>
            <td>Debit Balance Added:</td>
            <td class="text-right font-mono">Rs. {{ number_format($sale->balance_due, 2) }}</td>
        </tr>
        @if($sale->customer)
        <tr>
            <td>Updated Outstanding Tab:</td>
            <td class="text-right font-mono font-bold">Rs. {{ number_format($sale->customer->current_debt_balance, 2) }}</td>
        </tr>
        @endif
        @endif
    </table>

    @if($sale->payment_type === 'credit_lending')
    <div style="margin-top: 15px; text-align: center;">
        <p style="border-top: 1px dotted #000; width: 70%; margin: 15px auto 3px auto;"></p>
        <p style="font-size: 10px;">Customer Signature (Lending Agreement)</p>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for choosing PharmaCare!</p>
        <p>Medicines once sold will not be accepted without valid receipt.</p>
        <p>Keep out of reach of children. Store below 25°C.</p>
        <p>Powered by PharmaCare POS Pro</p>
    </div>

</body>
</html>
