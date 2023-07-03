<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifySalesForStoreCredits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->decimal('store_credits_used', 10, 2)->nullable()->after('order_total');
            $table->decimal('store_credits_invoiced', 10, 2)->nullable()->after('store_credits_used');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn('store_credits_invoiced');
            $table->dropColumn('store_credits_used');
        });
    }
}
