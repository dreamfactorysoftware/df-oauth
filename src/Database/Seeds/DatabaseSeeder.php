<?php
/**
 * This file is part of the DreamFactory(tm)
 *
 * DreamFactory(tm) <http://github.com/dreamfactorysoftware/rave>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
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