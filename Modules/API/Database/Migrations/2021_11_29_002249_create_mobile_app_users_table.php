<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMobileAppUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_app_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable(false)->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->default(null)->constrained('user_roles')->cascadeOnUpdate()->nullOnDelete();
            $table->string('access_token', 100)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->string('onesignal_player_id', 255)->nullable();
            $table->string('firebase_token_id', 255)->nullable();
            $table->double('last_seen_lat')->nullable();
            $table->double('last_seen_lng')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('logged_in')->default(0);
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
        Schema::dropIfExists('mobile_app_users');
    }
}
