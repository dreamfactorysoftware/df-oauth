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


use DreamFactory\DSP\OAuth\Components\FacebookProvider;
use DreamFactory\Library\Utility\ArrayUtils;
use Illuminate\Http\Request;
use DreamFactory\Rave\Utility\ResponseFactory;
use DreamFactory\Rave\Contracts\ServiceResponseInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Facebook extends BaseOAuthService
{
    protected function setDriver($config)
    {
        /** @var Request $request */
        $request = \Request::instance();
        $clientId = ArrayUtils::get($config, 'client_id');
        $clientSecret = ArrayUtils::get($config, 'client_secret');
        $redirectUrl = 'http://rave.local/fbcallback';

        $this->driver = new FacebookProvider($request, $clientId, $clientSecret, $redirectUrl);
    }

    protected function handlePOST()
    {
        if('session' === $this->resource)
        {
            /** @var RedirectResponse $response */
            $response = $this->driver->redirect();
            $url = $response->getTargetUrl();

            $this->response = ['response' => ['url' => $url]];

            return ResponseFactory::create( $this->response, $this->outputFormat, ServiceResponseInterface::HTTP_OK );
        }
    }
}