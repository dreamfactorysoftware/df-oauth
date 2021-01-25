<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class HerokuAddonSSOTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('heroku_addon_sso')) {
            Schema::create(
                'heroku_addon_sso',
                function (Blueprint $t){
                    $t->increments('id');
                    $t->integer('service_id')->unsigned()->index();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->string('secret');
                    $t->string('secret_type')->default('string');
                }
            );
        }
        if (!Schema::hasTable('heroku_users_map')) {
            Schema::create(
                'heroku_users_map',
                function (Blueprint $t){
                    $t->unsignedInteger('user_id')->index();
                    $t->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
                    $t->uuid('heroku_user_id');
                    $t->dateTime('updated_at');
                    $t->dateTime('created_at');
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('heroku_addon_sso');
        Schema::dropIfExists('heroku_users_map');
    }
}
