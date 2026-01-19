<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeOauthConfigRedirectUrlNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('oauth_config')) {
            Schema::table('oauth_config', function (Blueprint $table) {
                // Check if the column exists and is not already nullable
                if (Schema::hasColumn('oauth_config', 'redirect_url')) {
                    // Make redirect_url nullable to support Client Credentials flow
                    $table->string('redirect_url')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('oauth_config')) {
            Schema::table('oauth_config', function (Blueprint $table) {
                // Check if the column exists
                if (Schema::hasColumn('oauth_config', 'redirect_url')) {
                    // Revert redirect_url to not nullable
                    // Note: This may fail if there are existing null values
                    $table->string('redirect_url')->nullable(false)->change();
                }
            });
        }
    }
}