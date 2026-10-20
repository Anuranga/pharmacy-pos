<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('batch_number');
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->index();
            $table->decimal('purchase_cost', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('stock_quantity')->default(0); // in base UOM
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->timestamps();

            $table->unique(['product_id', 'batch_number'], 'product_batch_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_batches');
    }
};
