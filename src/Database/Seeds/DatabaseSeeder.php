<?php
/**
 * This file is part of the DreamFactory Rave(tm)
 *
 * DreamFactory Rave(tm) <http://github.com/dreamfactorysoftware/rave>
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

namespace DreamFactory\DSP\OAuth\Database\Seeds;

use Illuminate\Database\Seeder;
use DreamFactory\Rave\Models\ServiceType;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if ( !ServiceType::whereName( 'oauth_facebook' )->exists() )
        {
            // Add the service type
            ServiceType::create(
                [
                    'name'           => 'oauth_facebook',
                    'class_name'     => "DreamFactory\\DSP\\OAuth\\Services\\Facebook",
                    'config_handler' => "DreamFactory\\DSP\\OAuth\\Models\\OAuthConfig",
                    'label'          => 'Facebook OAuth',
                    'description'    => 'OAuth service for supporting Facebook authentication and API access.',
                    'group'          => 'oauth',
                    'singleton'      => 1
                ]
            );
            $this->command->info( 'Facebook OAuth service type seeded!' );
        }

        if ( !ServiceType::whereName( 'oauth_twitter' )->exists() )
        {
            // Add the service type
            ServiceType::create(
                [
                    'name'           => 'oauth_twitter',
                    'class_name'     => "DreamFactory\\DSP\\OAuth\\Services\\Twitter",
                    'config_handler' => "DreamFactory\\DSP\\OAuth\\Models\\OAuthConfig",
                    'label'          => 'Twitter OAuth',
                    'description'    => 'OAuth service for supporting Twitter authentication and API access.',
                    'group'          => 'oauth',
                    'singleton'      => 1
                ]
            );
            $this->command->info( 'Twitter OAuth service type seeded!' );
        }

        if ( !ServiceType::whereName( 'oauth_github' )->exists() )
        {
            // Add the service type
            ServiceType::create(
                [
                    'name'           => 'oauth_github',
                    'class_name'     => "DreamFactory\\DSP\\OAuth\\Services\\Github",
                    'config_handler' => "DreamFactory\\DSP\\OAuth\\Models\\OAuthConfig",
                    'label'          => 'Github OAuth',
                    'description'    => 'OAuth service for supporting Github authentication and API access.',
                    'group'          => 'oauth',
                    'singleton'      => 1
                ]
            );
            $this->command->info( 'Github OAuth service type seeded!' );
        }

        if ( !ServiceType::whereName( 'oauth_google' )->exists() )
        {
            // Add the service type
            ServiceType::create(
                [
                    'name'           => 'oauth_google',
                    'class_name'     => "DreamFactory\\DSP\\OAuth\\Services\\Google",
                    'config_handler' => "DreamFactory\\DSP\\OAuth\\Models\\OAuthConfig",
                    'label'          => 'Google OAuth',
                    'description'    => 'OAuth service for supporting Google authentication and API access.',
                    'group'          => 'oauth',
                    'singleton'      => 1
                ]
            );
            $this->command->info( 'Google OAuth service type seeded!' );
        }
    }
}