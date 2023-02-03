<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\GoogleProvider;
use Illuminate\Support\Arr;

class Google extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'google';

    /**
     * {@inheritdoc}
     */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');

        $this->provider = new GoogleProvider($clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}