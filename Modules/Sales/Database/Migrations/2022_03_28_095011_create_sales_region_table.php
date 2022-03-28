<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesRegionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_regions', function (Blueprint $table) {
            $table->id();
            $table->string('env', 20)->nullable(false);
            $table->string('channel', 20)->nullable(false);
            $table->unsignedBigInteger('entity_id')->nullable(false);
            $table->unsignedBigInteger('region_id')->nullable(false);
            $table->string('country_id', 10)->nullable(false);
            $table->string('name', 255)->nullable(false);
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
        Schema::dropIfExists('sales_regions');
    }
}
