<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserTableForOauthSupport extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('user', 'oauth_provider')) {
            Schema::table(
                'user',
                function (Blueprint $t){
                    $t->string('oauth_provider', 50)->nullable()->after('remember_token');
                }
            );
        }

        Schema::create(
            'oauth_config',
            function (Blueprint $t){
                $t->integer('service_id')->unsigned()->primary();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->integer('default_role')->unsigned()->nullable();
                // previously set to 'restrict' which isn't supported by all databases
                // removing the onDelete clause gets the same behavior as No Action and Restrict are defaults.
                $t->foreign('default_role')->references('id')->on('role');
                $t->string('client_id');
                $t->longText('client_secret');
                $t->string('redirect_url');
                $t->string('icon_class')->nullable();
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
        if (Schema::hasColumn('user', 'oauth_provider')) {
            Schema::table(
                'user',
                function (Blueprint $t){
                    $t->dropColumn('oauth_provider');
                }
            );
        }

        Schema::dropIfExists('oauth_config');
    }
}
