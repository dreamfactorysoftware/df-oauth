<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserTableForOauthSupport extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if(!Schema::hasColumn('user', 'oauth_provider'))
        {
            Schema::table('user', function(Blueprint $t)
                {
                    $t->string('oauth_provider', 50)->nullable()->after('remember_token');
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
        if(Schema::hasColumn('user', 'oauth_provider'))
        {
            Schema::table('user', function(Blueprint $t)
                {
                    $t->dropColumn('oauth_provider');
                }
            );
        }
	}

}
