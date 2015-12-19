<?php

namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\LinkedInProvider;
use DreamFactory\Library\Utility\ArrayUtils;

class LinkedIn extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'linkedin';

    /** @inheritdoc */
    protected function setDriver($config)
    {
        $clientId = ArrayUtils::get($config, 'client_id');
        $clientSecret = ArrayUtils::get($config, 'client_secret');
        $redirectUrl = ArrayUtils::get($config, 'redirect_url');

        $this->driver = new LinkedInProvider($clientId, $clientSecret, $redirectUrl);
    }

    /** @inheritdoc */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}