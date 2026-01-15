<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAllowNewUsersToOauthConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('oauth_config', 'allow_new_users')) {
            Schema::table(
                'oauth_config',
                function (Blueprint $t){
                    $t->boolean('allow_new_users')->default(1)->after('custom_provider');
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
        if (Schema::hasColumn('oauth_config', 'allow_new_users')) {
            Schema::table(
                'oauth_config',
                function (Blueprint $t){
                    $t->dropColumn('allow_new_users');
                }
            );
        }
    }
}