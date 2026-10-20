<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique()->index();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date');
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->enum('status', ['Received', 'Draft'])->default('Received');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grns');
    }
};
