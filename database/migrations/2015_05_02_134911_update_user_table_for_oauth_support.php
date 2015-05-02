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
        if ( !Schema::hasColumn( 'user', 'oauth_provider' ) )
        {
            Schema::table(
                'user',
                function ( Blueprint $t )
                {
                    $t->string( 'oauth_provider', 50 )->nullable()->after( 'remember_token' );
                }
            );
        }

        Schema::create(
            'oauth_config',
            function ( Blueprint $t )
            {
                $t->integer( 'service_id' )->unsigned()->primary();
                $t->foreign( 'service_id' )->references( 'id' )->on( 'service' )->onDelete( 'cascade' );
                $t->string( 'provider' );
                $t->string( 'client_id' );
                $t->longText( 'client_secret' );
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
        if ( Schema::hasColumn( 'user', 'oauth_provider' ) )
        {
            Schema::table(
                'user',
                function ( Blueprint $t )
                {
                    $t->dropColumn( 'oauth_provider' );
                }
            );
        }

        Schema::dropIfExists('oauth_config');
    }

}
