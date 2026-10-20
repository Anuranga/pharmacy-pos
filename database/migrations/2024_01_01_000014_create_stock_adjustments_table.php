<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('batch_id')->constrained('product_batches')->onDelete('cascade');
            $table->foreignId('adjusted_by')->constrained('users')->onDelete('restrict');
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->enum('reason', ['Damage', 'Expiry', 'Breakage', 'Audit Recount', 'Other']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
