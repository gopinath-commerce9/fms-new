<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyUsersTableForAcl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 512)->nullable(false);
            $table->string('display_name', 512)->nullable(false);
            $table->text('description')->nullable()->default(null);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('user_role_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable(false)->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->default(null)->constrained('user_roles')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1024)->nullable(false);
            $table->string('display_name', 1024)->nullable(false);
            $table->text('description')->nullable()->default(null);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('permission_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable(false)->constrained('user_roles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('permission_id')->nullable(false)->constrained('permissions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('permitted')->default(0);
            $table->boolean('is_active')->default(1);
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

        Schema::dropIfExists('permission_maps');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_maps');
        Schema::dropIfExists('roles');

    }
}
