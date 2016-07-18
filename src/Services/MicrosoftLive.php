<?php
/**
 * Created by PhpStorm.
 * User: arif
 * Date: 7/18/16
 * Time: 10:35 AM
 */

namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\MicrosoftLiveProvider;

class MicrosoftLive extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'microsoft-live';

    /** @inheritdoc */
    protected function setDriver($config)
    {
        $clientId = array_get($config, 'client_id');
        $clientSecret = array_get($config, 'client_secret');
        $redirectUrl = array_get($config, 'redirect_url');

        $this->driver = new MicrosoftLiveProvider($clientId, $clientSecret, $redirectUrl);
    }

    /** @inheritdoc */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}