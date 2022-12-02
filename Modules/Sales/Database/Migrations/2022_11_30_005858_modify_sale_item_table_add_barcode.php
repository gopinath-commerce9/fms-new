<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifySaleItemTableAddBarcode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->text('scan_barcode')->nullable()->default(null)->after('item_image');
            $table->unsignedInteger('scan_count')->nullable()->after('scan_barcode');
            $table->unsignedDecimal('qty_delivered', 7, 3)->nullable()->after('qty_refunded');
            $table->decimal('row_total_delivered', 10, 2)->nullable()->after('row_grand_total');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->dropColumn('scan_barcode');
            $table->dropColumn('scan_count');
            $table->dropColumn('qty_delivered');
            $table->dropColumn('row_total_delivered');
        });

    }
}
