<?php
namespace DreamFactory\Core\OAuth\Database\Seeds;

use DreamFactory\Core\Database\Seeds\BaseModelSeeder;

class DatabaseSeeder extends BaseModelSeeder
{
    protected $modelClass = 'DreamFactory\\Core\\Models\\ServiceType';

    protected $records = [
        [
            'name'           => 'oauth_facebook',
            'class_name'     => "DreamFactory\\Core\\OAuth\\Services\\Facebook",
            'config_handler' => "DreamFactory\\Core\\OAuth\\Models\\OAuthConfig",
            'label'          => 'Facebook OAuth',
            'description'    => 'OAuth service for supporting Facebook authentication and API access.',
            'group'          => 'oauth',
            'singleton'      => 1
        ],
        [
            'name'           => 'oauth_twitter',
            'class_name'     => "DreamFactory\\Core\\OAuth\\Services\\Twitter",
            'config_handler' => "DreamFactory\\Core\\OAuth\\Models\\OAuthConfig",
            'label'          => 'Twitter OAuth',
            'description'    => 'OAuth service for supporting Twitter authentication and API access.',
            'group'          => 'oauth',
            'singleton'      => 1
        ],
        [
            'name'           => 'oauth_github',
            'class_name'     => "DreamFactory\\Core\\OAuth\\Services\\Github",
            'config_handler' => "DreamFactory\\Core\\OAuth\\Models\\OAuthConfig",
            'label'          => 'Github OAuth',
            'description'    => 'OAuth service for supporting Github authentication and API access.',
            'group'          => 'oauth',
            'singleton'      => 1
        ],
        [
            'name'           => 'oauth_google',
            'class_name'     => "DreamFactory\\Core\\OAuth\\Services\\Google",
            'config_handler' => "DreamFactory\\Core\\OAuth\\Models\\OAuthConfig",
            'label'          => 'Google OAuth',
            'description'    => 'OAuth service for supporting Google authentication and API access.',
            'group'          => 'oauth',
            'singleton'      => 1
        ]
    ];
}