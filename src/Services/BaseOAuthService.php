<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\OAuthServiceTrait;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Services\BaseRestService;

abstract class BaseOAuthService extends BaseRestService
{
    use OAuthServiceTrait;

    /**
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $settings = (array)$settings;
        $settings['verbAliases'] = [
            Verbs::PUT   => Verbs::POST,
            Verbs::MERGE => Verbs::PATCH
        ];

        parent::__construct($settings);

        $config = array_get($settings, 'config');
        $this->defaultRole = array_get($config, 'default_role');
        $this->setProvider($config);
    }
}