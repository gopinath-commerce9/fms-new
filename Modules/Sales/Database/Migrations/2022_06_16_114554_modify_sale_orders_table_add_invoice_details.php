<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifySaleOrdersTableAddInvoiceDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->decimal('canceled_total', 10, 2)->nullable()->after('order_total');
            $table->decimal('invoiced_total', 10, 2)->nullable()->after('canceled_total');
            $table->dateTime('invoiced_at')->nullable()->after('order_status_label');
            $table->unsignedBigInteger('invoice_id')->nullable()->after('invoiced_at');
            $table->string('invoice_number', 50)->nullable()->after('invoice_id');
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
            $table->dropColumn('canceled_total');
            $table->dropColumn('invoiced_total');
            $table->dropColumn('invoiced_at');
            $table->dropColumn('invoice_id');
            $table->dropColumn('invoice_number');
        });
    }
}
