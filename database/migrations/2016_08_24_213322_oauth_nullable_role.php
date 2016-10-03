<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OauthNullableRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('oauth_config', function (Blueprint $table) {
            $table->integer('default_role')->unsigned()->nullable()->change();
        });

        Schema::create(
            'oauth_token_map',
            function (Blueprint $t){
                $t->increments('id');
                $t->integer('user_id')->unsigned()->index();
                $t->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
                $t->integer('service_id')->unsigned()->index();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->text('token');
                $t->longText('response');
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
        Schema::table('oauth_config', function (Blueprint $table) {
            //
        });

        Schema::dropIfExists('oauth_token_map');
    }
}
