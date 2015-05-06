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

namespace DreamFactory\DSP\OAuth\Services;

use DreamFactory\DSP\OAuth\Models\OAuthConfig;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Rave\Contracts\ServiceResponseInterface;
use DreamFactory\Rave\Services\BaseRestService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use DreamFactory\Rave\Utility\ResponseFactory;
use Laravel\Socialite\Contracts\Provider;

abstract class BaseOAuthService extends BaseRestService
{
    /**
     * Callback handler url
     * @var string
     */
    protected $redirectUrl;

    /**
     * OAuth service provider.
     * @var Provider
     */
    protected $driver;

    /**
     * Default role id configured for this OAuth service.
     * @var integer
     */
    protected $defaultRole;

    /**
     * @param array $settings
     */
    public function __construct( $settings = [ ] )
    {
        $verbAliases = [
            Verbs::PUT   => Verbs::POST,
            Verbs::MERGE => Verbs::PATCH
        ];
        ArrayUtils::set( $settings, "verbAliases", $verbAliases );
        parent::__construct( $settings );

        $config = ArrayUtils::get( $settings, 'config' );
        $this->defaultRole = ArrayUtils::get($config, 'default_role');
        $this->redirectUrl = OAuthConfig::generateRedirectUrl($this->name);
        $this->setDriver( $config );
    }

    /**
     * Sets the OAuth service provider.
     *
     * @param array $config
     *
     * @return mixed
     */
    abstract protected function setDriver( $config );

    /**
     * Returns the OAuth provider name.
     *
     * @return string
     */
    abstract public function getProviderName();

    /**
     * Handles POST request on this service.
     *
     * @return array|bool|RedirectResponse
     */
    protected function handlePOST()
    {
        if('session' === $this->resource)
        {
            /** @var RedirectResponse $response */
            $response = $this->driver->redirect();
            $url = $response->getTargetUrl();

            /** @var Request $request */
            $request = $this->request->getDriver();

            if($request->ajax())
            {
                $result= [ 'response' => [ 'login_url' => $url ] ];

                return $result;
            }
            else{
                return $response;
            }
        }
        return false;
    }

    /**
     * @return ServiceResponseInterface
     */
    protected function respond()
    {
        if ( $this->response instanceof ServiceResponseInterface || $this->response instanceof RedirectResponse)
        {
            return $this->response;
        }

        return ResponseFactory::create( $this->response, $this->outputFormat, ServiceResponseInterface::HTTP_OK );
    }

    /**
     * Returns the OAuth service provider.
     *
     * @return Provider
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns the service name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the default role id configured for this service.
     *
     * @return int|mixed
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }
}