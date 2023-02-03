<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\FacebookProvider;
use Illuminate\Support\Arr;

class Facebook extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'facebook';

    /**
     * {@inheritdoc}
     */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');

        $this->provider = new FacebookProvider($clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}