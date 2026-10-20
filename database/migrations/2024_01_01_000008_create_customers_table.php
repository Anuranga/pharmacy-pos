<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('phone')->unique();
            $table->string('nic_number')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->decimal('current_debt_balance', 12, 2)->default(0.00); // Accounts Receivable
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
