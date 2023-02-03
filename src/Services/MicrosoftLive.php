<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\MicrosoftLiveIntProvider;
use DreamFactory\Core\OAuth\Components\MicrosoftLiveProvider;
use Illuminate\Support\Arr;

class MicrosoftLive extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'microsoft-live';

    /** @inheritdoc */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');
        $customProvider = Arr::get($config, 'custom_provider');

        if (!empty($customProvider) && boolval($customProvider) === true) {
            $this->provider = new MicrosoftLiveIntProvider($clientId, $clientSecret, $redirectUrl);
        } else {
            $this->provider = new MicrosoftLiveProvider($clientId, $clientSecret, $redirectUrl);
        }
    }

    /** @inheritdoc */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}