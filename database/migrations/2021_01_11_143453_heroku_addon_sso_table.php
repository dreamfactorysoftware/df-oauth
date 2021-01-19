<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class HerokuAddonSSOTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('heroku_addon_sso');
    }
}
