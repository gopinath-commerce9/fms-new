<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('env', 20)->nullable(false);
            $table->string('channel', 20)->nullable(false);
            $table->unsignedBigInteger('product_id')->nullable(false);
            $table->string('product_sku', 100)->nullable(false);
            $table->text('product_name')->nullable(false);
            $table->unsignedBigInteger('category_id')->nullable(false);
            $table->string('category_name', 100)->nullable(false);
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
        Schema::dropIfExists('product_categories');
    }
}
