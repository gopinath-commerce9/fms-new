<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetupSalesRelatedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('sale_customers', function (Blueprint $table) {
            $table->id();
            $table->string('env', 20)->nullable(false);
            $table->string('channel', 20)->nullable(false);
            $table->string('contact_number', 30)->nullable(false);
            $table->string('email_id', 255)->nullable();
            $table->string('customer_group_id', 255)->nullable();
            $table->string('sale_customer_id', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('gender', 50)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->string('env', 20)->nullable(false);
            $table->string('channel', 20)->nullable(false);
            $table->unsignedBigInteger('order_id')->nullable(false);
            $table->unsignedBigInteger('increment_id')->nullable(false);
            $table->dateTime('order_created_at')->nullable();
            $table->dateTime('order_updated_at')->nullable();
            $table->foreignId('customer_id')->nullable()->default(null)->constrained('sale_customers')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('is_guest')->default(1);
            $table->string('customer_firstname', 100)->nullable();
            $table->string('customer_lastname', 100)->nullable();
            $table->integer('region_id')->nullable();
            $table->string('region_code', 255)->nullable();
            $table->string('region', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('zone_id', 30)->nullable();
            $table->text('store')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time_slot', 100)->nullable();
            $table->unsignedInteger('total_item_count')->nullable();
            $table->unsignedDecimal('total_qty_ordered', 7, 3)->nullable();
            $table->decimal('order_weight', 10, 3)->nullable();
            $table->unsignedInteger('box_count')->nullable();
            $table->boolean('not_require_pack')->default(1);
            $table->string('order_currency', 10)->nullable();
            $table->decimal('order_subtotal', 10, 2)->nullable();
            $table->decimal('order_tax', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('shipping_total', 10, 2)->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->decimal('order_total', 10, 2)->nullable();
            $table->decimal('order_due', 10, 2)->nullable();
            $table->string('order_state', 30)->nullable();
            $table->string('order_status', 30)->nullable();
            $table->string('order_status_label', 100)->nullable();
            $table->boolean('to_be_synced')->default(0);
            $table->boolean('is_synced')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable(false)->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable(false);
            $table->unsignedBigInteger('sale_order_id')->nullable(false);
            $table->dateTime('item_created_at')->nullable();
            $table->dateTime('item_updated_at')->nullable();
            $table->unsignedBigInteger('product_id')->nullable(false);
            $table->string('product_type', 50)->nullable(false);
            $table->string('item_sku', 100)->nullable(false);
            $table->string('item_barcode', 30)->nullable(false);
            $table->text('item_name')->nullable(false);
            $table->text('item_info')->nullable(false);
            $table->text('item_image')->nullable();
            $table->unsignedDecimal('actual_qty', 7, 3)->nullable();
            $table->unsignedDecimal('qty_ordered', 7, 3)->nullable();
            $table->unsignedDecimal('qty_shipped', 7, 3)->nullable();
            $table->unsignedDecimal('qty_invoiced', 7, 3)->nullable();
            $table->unsignedDecimal('qty_canceled', 7, 3)->nullable();
            $table->unsignedDecimal('qty_returned', 7, 3)->nullable();
            $table->unsignedDecimal('qty_refunded', 7, 3)->nullable();
            $table->string('selling_unit', 30)->nullable();
            $table->string('selling_unit_label', 60)->nullable();
            $table->string('billing_period', 255)->nullable();
            $table->string('delivery_day', 60)->nullable();
            $table->string('scale_number', 60)->nullable();
            $table->string('country_label', 60)->nullable();
            $table->decimal('item_weight', 10, 3)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('row_total', 10, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('tax_percent', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('discount_percent', 10, 2)->nullable();
            $table->decimal('row_grand_total', 10, 2)->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->boolean('vendor_availability')->default(0);
            $table->boolean('store_availability')->nullable();
            $table->dateTime('availability_checked_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('sale_order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable(false)->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('address_id')->nullable(false);
            $table->unsignedBigInteger('sale_order_id')->nullable(false);
            $table->string('type', 100)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('email_id', 255)->nullable();
            $table->string('address_1', 255)->nullable();
            $table->string('address_2', 255)->nullable();
            $table->string('address_3', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->integer('region_id')->nullable();
            $table->string('region_code', 255)->nullable();
            $table->string('region', 255)->nullable();
            $table->string('country_id', 30)->nullable();
            $table->string('post_code', 30)->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('sale_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable(false)->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('payment_id')->nullable(false);
            $table->unsignedBigInteger('sale_order_id')->nullable(false);
            $table->string('method', 255)->nullable();
            $table->decimal('amount_payable', 10, 2)->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('cc_last4', 10)->nullable();
            $table->string('cc_start_month', 10)->nullable();
            $table->string('cc_start_year', 10)->nullable();
            $table->string('cc_exp_year', 10)->nullable();
            $table->decimal('shipping_amount', 10, 2)->nullable();
            $table->decimal('shipping_captured', 10, 2)->nullable();
            $table->text('extra_info')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('sale_order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable(false)->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('history_id')->nullable(false);
            $table->unsignedBigInteger('sale_order_id')->nullable(false);
            $table->string('name', 255)->nullable();
            $table->string('status', 255)->nullable();
            $table->string('comments', 255)->nullable();
            $table->dateTime('status_created_at')->nullable();
            $table->boolean('customer_notified')->nullable();
            $table->boolean('visible_on_front')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('sale_order_process_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable(false)->constrained('sale_orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('action', 255)->nullable();
            $table->boolean('status')->nullable();
            $table->text('comments')->nullable();
            $table->text('extra_info')->nullable();
            $table->foreignId('done_by')->nullable()->default(null)->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime('done_at')->nullable();
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

        Schema::dropIfExists('sale_order_process_histories');
        Schema::dropIfExists('sale_order_status_histories');
        Schema::dropIfExists('sale_order_payments');
        Schema::dropIfExists('sale_order_addresses');
        Schema::dropIfExists('sale_order_items');
        Schema::dropIfExists('sale_orders');
        Schema::dropIfExists('sale_customers');

    }
}
