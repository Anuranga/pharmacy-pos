<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grn_items', function (Blueprint $table) {
            $table->string('purchase_uom', 20)->default('unit')->after('expiry_date');
            $table->integer('units_per_pack')->default(1)->after('purchase_uom');
            $table->integer('packs_received')->nullable()->after('units_per_pack');
            $table->integer('bonus_packs')->nullable()->after('packs_received');
            $table->decimal('pack_cost_price', 12, 2)->nullable()->after('bonus_packs');
            $table->decimal('pack_retail_price', 12, 2)->nullable()->after('pack_cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('grn_items', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_uom',
                'units_per_pack',
                'packs_received',
                'bonus_packs',
                'pack_cost_price',
                'pack_retail_price',
            ]);
        });
    }
};

