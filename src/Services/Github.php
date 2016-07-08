<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\GithubProvider;

class Github extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'github';

    /**
     * {@inheritdoc}
     */
    protected function setDriver($config)
    {
        $clientId = array_get($config, 'client_id');
        $clientSecret = array_get($config, 'client_secret');
        $redirectUrl = array_get($config, 'redirect_url');

        $this->driver = new GithubProvider($clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}