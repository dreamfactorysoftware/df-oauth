<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddingACustomFieldForOauthConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('oauth_config', 'custom_provider')) {
            Schema::table(
                'oauth_config',
                function (Blueprint $t){
                    $t->boolean('custom_provider')->default(0);
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
        if (Schema::hasColumn('oauth_config', 'custom_provider')) {
            Schema::table(
                'oauth_config',
                function (Blueprint $t){
                    $t->dropColumn('custom_provider');
                }
            );
        }
    }
}
