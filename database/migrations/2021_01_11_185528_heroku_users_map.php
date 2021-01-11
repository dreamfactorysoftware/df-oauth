<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class HerokuUsersMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'heroku_users_map',
            function (Blueprint $t){
                $t->increments('user_id')->index();
                $t->uuid('heroku_user_id');
                $t->dateTime('updated_at');
                $t->dateTime('created_at');
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
        Schema::dropIfExists('heroku_users_map');
    }
}
