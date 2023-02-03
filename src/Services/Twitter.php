<?php

namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\TwitterProvider;
use Illuminate\Support\Arr;

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
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');

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