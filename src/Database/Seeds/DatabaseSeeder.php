<?php
namespace DreamFactory\Core\OAuth\Database\Seeds;

use DreamFactory\Core\Database\Seeds\BaseModelSeeder;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Models\ServiceType;
use DreamFactory\Core\OAuth\Models\OAuthConfig;
use DreamFactory\Core\OAuth\Services\Facebook;
use DreamFactory\Core\OAuth\Services\Github;
use DreamFactory\Core\OAuth\Services\Google;
use DreamFactory\Core\OAuth\Services\Twitter;

class DatabaseSeeder extends BaseModelSeeder
{
    protected $modelClass = ServiceType::class;

    protected $records = [
        [
            'name'           => 'oauth_facebook',
            'class_name'     => Facebook::class,
            'config_handler' => OAuthConfig::class,
            'label'          => 'Facebook OAuth',
            'description'    => 'OAuth service for supporting Facebook authentication and API access.',
            'group'          => ServiceTypeGroups::OAUTH,
            'singleton'      => false
        ],
        [
            'name'           => 'oauth_twitter',
            'class_name'     => Twitter::class,
            'config_handler' => OAuthConfig::class,
            'label'          => 'Twitter OAuth',
            'description'    => 'OAuth service for supporting Twitter authentication and API access.',
            'group'          => ServiceTypeGroups::OAUTH,
            'singleton'      => false
        ],
        [
            'name'           => 'oauth_github',
            'class_name'     => Github::class,
            'config_handler' => OAuthConfig::class,
            'label'          => 'GitHub OAuth',
            'description'    => 'OAuth service for supporting GitHub authentication and API access.',
            'group'          => ServiceTypeGroups::OAUTH,
            'singleton'      => false
        ],
        [
            'name'           => 'oauth_google',
            'class_name'     => Google::class,
            'config_handler' => OAuthConfig::class,
            'label'          => 'Google OAuth',
            'description'    => 'OAuth service for supporting Google authentication and API access.',
            'group'          => ServiceTypeGroups::OAUTH,
            'singleton'      => false
        ]
    ];
}