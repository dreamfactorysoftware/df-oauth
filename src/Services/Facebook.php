<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\FacebookProvider;
use DreamFactory\Library\Utility\ArrayUtils;

class Facebook extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'facebook';

    /**
     * {@inheritdoc}
     */
    protected function setDriver($config)
    {
        $clientId = ArrayUtils::get($config, 'client_id');
        $clientSecret = ArrayUtils::get($config, 'client_secret');

        $this->driver = new FacebookProvider($clientId, $clientSecret, $this->redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}