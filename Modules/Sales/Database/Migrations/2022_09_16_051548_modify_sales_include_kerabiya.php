<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifySalesIncludeKerabiya extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('sales_regions', function (Blueprint $table) {
            $table->boolean('kerabiya_access')->default(0)->after('country_id');
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->boolean('is_kerabiya_delivery')->default(0)->after('is_synced');
            $table->dateTime('kerabiya_set_at')->nullable()->after('is_kerabiya_delivery');
            $table->foreignId('kerabiya_set_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete()->after('kerabiya_set_at');
            $table->string('kerabiya_awb_number', 50)->nullable()->after('kerabiya_set_by');
            $table->text('kerabiya_awb_pdf')->nullable()->after('kerabiya_awb_number');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('sales_regions', function (Blueprint $table) {
            $table->dropColumn('kerabiya_access');
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn('is_kerabiya_delivery');
            $table->dropColumn('kerabiya_set_at');
            $table->dropForeign(['kerabiya_set_by']);
            $table->dropColumn('kerabiya_set_by');
            $table->dropColumn('kerabiya_awb_number');
            $table->dropColumn('kerabiya_awb_pdf');
        });

    }
}
