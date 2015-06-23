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

namespace DreamFactory\Core\OAuth\Components;


use DreamFactory\Core\Utility\Session;
use Laravel\Socialite\One\AbstractProvider;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use Illuminate\Http\RedirectResponse;

trait DFOAuthOneProvider
{
    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;

    /**
     * Determine if the provider is operating with state.
     *
     * @return bool
     */
    protected function usesState()
    {
        return ! $this->stateless;
    }

    /**
     * Determine if the provider is operating as stateless.
     *
     * @return bool
     */
    protected function isStateless()
    {
        return $this->stateless;
    }

    /**
     * Indicates that the provider should operate as stateless.
     *
     * @return $this
     */
    public function stateless()
    {
        $this->stateless = true;

        return $this;
    }

    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return RedirectResponse
     */
    public function redirect()
    {
        /** @type TemporaryCredentials $temp */
        $temp = $this->server->getTemporaryCredentials();

        if($this->usesState()) {
            $this->request->getSession()->set(
                'oauth.temp', $temp
            );
        }
        else{
            $identifier = $temp->getIdentifier();
            \Cache::put($identifier, $temp, 1);
        }

        return new RedirectResponse($this->server->getAuthorizationUrl($temp));
    }

    /**
     * Get the token credentials for the request.
     *
     * @return \League\OAuth1\Client\Credentials\TokenCredentials
     */
    protected function getToken()
    {
        $temp = null;

        if($this->usesState()) {
            $temp = $this->request->getSession()->get('oauth.temp');
        }
        else{
            $key = $this->request->get('oauth_token');
            $temp = \Cache::pull($key);
        }

        return $this->server->getTokenCredentials(
            $temp, $this->request->get('oauth_token'), $this->request->get('oauth_verifier')
        );
    }
}