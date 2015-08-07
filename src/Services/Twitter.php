<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\OAuth\Components\TwitterProvider;

class Twitter extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'twitter';

    /**
     * {@inheritdoc}
     */
    protected function setDriver($config)
    {
        $clientId = ArrayUtils::get($config, 'client_id');
        $clientSecret = ArrayUtils::get($config, 'client_secret');
        $redirectUrl = ArrayUtils::get($config, 'redirect_url');

        $this->driver = new TwitterProvider($clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}