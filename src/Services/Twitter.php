<?php

namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\TwitterProvider;

class Twitter extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'twitter';

    /** @type array Service Resources */
    protected static $resources = [];

    /**
     * {@inheritdoc}
     */
    protected function setProvider($config)
    {
        $clientId = array_get($config, 'client_id');
        $clientSecret = array_get($config, 'client_secret');
        $redirectUrl = array_get($config, 'redirect_url');

        $this->provider = new TwitterProvider($clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}