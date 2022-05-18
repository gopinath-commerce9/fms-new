<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifySalesOrderTableAmountVerification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->boolean('is_amount_verified')->default(0)->after('is_active');
            $table->dateTime('amount_verified_at')->nullable()->after('is_amount_verified');
            $table->foreignId('amount_verified_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
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
            $table->dropColumn('is_amount_verified');
            $table->dropColumn('amount_verified_at');
            $table->dropForeign(['amount_verified_by']);
            $table->dropColumn('amount_verified_by');
        });
    }
}
