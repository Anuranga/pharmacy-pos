<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customer Sales Returns (POS Billed Item Returns)
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('processed_by')->constrained('users');
            $table->decimal('total_refund_amount', 12, 2)->default(0);
            $table->string('refund_method', 30)->default('cash'); // cash, credit_reduction, exchange
            $table->string('reason', 100)->default('Customer Return');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->integer('quantity_returned');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('restock_status', 20)->default('restocked'); // restocked, discarded
            $table->timestamps();
        });

        // 2. Shop / Supplier GRN Returns (Purchase Returns / Debit Notes)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('grn_id')->nullable()->constrained('grns')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('processed_by')->constrained('users');
            $table->decimal('total_return_amount', 12, 2)->default(0);
            $table->string('settlement_method', 30)->default('payable_deduction'); // payable_deduction, cash_refund, replacement
            $table->string('reason', 100)->default('Supplier GRN Return');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('grn_item_id')->nullable()->constrained('grn_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('batch_number', 80)->nullable();
            $table->string('return_uom', 20)->default('unit'); // unit, pack
            $table->integer('units_per_pack')->default(1);
            $table->integer('packs_returned')->default(0);
            $table->integer('quantity_returned');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
