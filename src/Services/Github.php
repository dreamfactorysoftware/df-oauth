<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\GithubProvider;
use Illuminate\Support\Arr;

class Github extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'github';

    /**
     * {@inheritdoc}
     */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');

        $this->provider = new GithubProvider($clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}