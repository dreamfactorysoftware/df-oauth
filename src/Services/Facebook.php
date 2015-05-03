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
    const PROVIDER_NAME = 'facebook';

    protected function setDriver($config)
    {
        $clientId = ArrayUtils::get($config, 'client_id');
        $clientSecret = ArrayUtils::get($config, 'client_secret');
        $redirectPath = self::CALLBACK_PATH.'?service='.$this->name;

        $this->driver = new FacebookProvider($clientId, $clientSecret, $redirectPath);
    }

    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }
}