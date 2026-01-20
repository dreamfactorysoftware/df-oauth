<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAzureAdClientCredentialsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('oauth_config', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('redirect_url');
            $table->string('authority_url')->nullable()->after('tenant_id');
            $table->text('scopes')->nullable()->after('authority_url');
            $table->string('grant_type')->default('authorization_code')->after('scopes');
            $table->boolean('is_client_credentials')->default(false)->after('grant_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('oauth_config', function (Blueprint $table) {
            $table->dropColumn(['tenant_id', 'authority_url', 'scopes', 'grant_type', 'is_client_credentials']);
        });
    }
}