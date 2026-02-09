<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddGoogleGroupRoleMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add map_group_to_role column to oauth_config table
        if (!Schema::hasColumn('oauth_config', 'map_group_to_role')) {
            Schema::table('oauth_config', function (Blueprint $t) {
                $t->boolean('map_group_to_role')->default(0);
            });
        }

        // Create role_google table for group-to-role mappings
        if (!Schema::hasTable('role_google')) {
            Schema::create('role_google', function (Blueprint $t) {
                $t->integer('role_id')->unsigned()->primary();
                $t->foreign('role_id')->references('id')->on('role')->onDelete('cascade');
                $t->string('group_email', 255);
                $t->index('group_email');
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
        Schema::dropIfExists('role_google');

        if (Schema::hasColumn('oauth_config', 'map_group_to_role')) {
            Schema::table('oauth_config', function (Blueprint $t) {
                $t->dropColumn('map_group_to_role');
            });
        }
    }
}
