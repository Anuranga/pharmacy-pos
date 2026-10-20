<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode_sku')->unique()->index();
            $table->string('brand_name')->index();
            $table->string('generic_name')->nullable()->index();
            $table->string('dosage_strength')->nullable();
            $table->string('item_type')->default('tablet'); // tablet, syrup, injection, capsule, etc.
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('base_uom')->default('Unit'); // Tablet, Bottle, Vial, etc.
            $table->integer('units_per_pack')->default(1);
            $table->integer('reorder_min')->default(10);
            $table->integer('reorder_max')->default(100);
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_controlled')->default(false);
            $table->decimal('unit_cost_price', 12, 2)->default(0.00);
            $table->decimal('unit_retail_price', 12, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
