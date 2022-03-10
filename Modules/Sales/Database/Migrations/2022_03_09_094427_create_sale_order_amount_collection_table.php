<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSaleOrderAmountCollectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_order_amount_collection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable(false)->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('method', 255)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_order_amount_collection');
    }
}
