<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\GoogleProvider;
use DreamFactory\Library\Utility\ArrayUtils;

class Google extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'google';

    /**
     * {@inheritdoc}
     */
    protected function setDriver($config)
    {
        $clientId = ArrayUtils::get($config, 'client_id');
        $clientSecret = ArrayUtils::get($config, 'client_secret');
        $this->driver = new GoogleProvider($clientId, $clientSecret, $this->redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}