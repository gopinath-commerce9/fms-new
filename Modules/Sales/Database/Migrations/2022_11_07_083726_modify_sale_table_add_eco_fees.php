<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifySaleTableAddEcoFees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->decimal('eco_friendly_packing_fee', 10, 2)->nullable()->after('order_total');
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
            $table->dropColumn('eco_friendly_packing_fee');
        });

    }
}
