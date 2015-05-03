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

use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Rave\Contracts\ServiceResponseInterface;
use DreamFactory\Rave\Services\BaseRestService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use DreamFactory\Rave\Utility\ResponseFactory;

abstract class BaseOAuthService extends BaseRestService
{
    const CALLBACK_PATH = 'dsp/oauth/callback';

    protected $driver;

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
        $this->setDriver( $config );
    }

    abstract protected function setDriver( $config );

    abstract public function getProviderName();

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

    public function getDriver()
    {
        return $this->driver;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefaultRole()
    {
        return $this->defaultRole;
    }
}