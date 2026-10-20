<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('grns')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('batch_number');
            $table->date('expiry_date');
            $table->decimal('cost_price', 12, 2);
            $table->decimal('retail_price', 12, 2);
            $table->integer('quantity_received');
            $table->integer('free_bonus_qty')->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grn_items');
    }
};
