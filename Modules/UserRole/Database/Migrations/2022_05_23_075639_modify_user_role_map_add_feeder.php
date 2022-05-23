<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyUserRoleMapAddFeeder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_role_maps', function (Blueprint $table) {
            $table->boolean('is_feeder_driver')->default(0)->after('role_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_role_maps', function (Blueprint $table) {
            $table->dropColumn('is_feeder_driver');
        });
    }
}
