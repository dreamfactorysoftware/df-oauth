<?php
namespace DreamFactory\Core\OAuth;

use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\OAuth\Models\OAuthConfig;
use DreamFactory\Core\OAuth\Services\Facebook;
use DreamFactory\Core\OAuth\Services\Github;
use DreamFactory\Core\OAuth\Services\Google;
use DreamFactory\Core\OAuth\Services\LinkedIn;
use DreamFactory\Core\OAuth\Services\Twitter;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'           => 'oauth_facebook',
                    'label'          => 'Facebook OAuth',
                    'description'    => 'OAuth service for supporting Facebook authentication and API access.',
                    'group'          => ServiceTypeGroups::OAUTH,
                    'config_handler' => OAuthConfig::class,
                    'factory'        => function ($config){
                        return new Facebook($config);
                    },
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'           => 'oauth_twitter',
                    'label'          => 'Twitter OAuth',
                    'description'    => 'OAuth service for supporting Twitter authentication and API access.',
                    'group'          => ServiceTypeGroups::OAUTH,
                    'config_handler' => OAuthConfig::class,
                    'factory'        => function ($config){
                        return new Twitter($config);
                    },
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'           => 'oauth_github',
                    'label'          => 'GitHub OAuth',
                    'description'    => 'OAuth service for supporting GitHub authentication and API access.',
                    'group'          => ServiceTypeGroups::OAUTH,
                    'config_handler' => OAuthConfig::class,
                    'factory'        => function ($config){
                        return new Github($config);
                    },
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'           => 'oauth_google',
                    'label'          => 'Google OAuth',
                    'description'    => 'OAuth service for supporting Google authentication and API access.',
                    'group'          => ServiceTypeGroups::OAUTH,
                    'config_handler' => OAuthConfig::class,
                    'factory'        => function ($config){
                        return new Google($config);
                    },
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'           => 'oauth_linkedin',
                    'label'          => 'LinkedIn OAuth',
                    'description'    => 'OAuth service for supporting LinkedIn authentication and API access.',
                    'group'          => ServiceTypeGroups::OAUTH,
                    'config_handler' => OAuthConfig::class,
                    'factory'        => function ($config){
                        return new LinkedIn($config);
                    },
                ])
            );
        });
    }
}