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

namespace DreamFactory\DSP\OAuth\Models;

use DreamFactory\DSP\OAuth\Services\BaseOAuthService;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Rave\Contracts\ServiceConfigHandlerInterface;
use DreamFactory\Rave\Models\BaseServiceConfigModel;

/**
 * Class OAuthConfig
 *
 * @package DreamFactory\DSP\OAuth\Models
 */
class OAuthConfig extends BaseServiceConfigModel implements ServiceConfigHandlerInterface
{
    /**
     * Callback handler base route
     */
    const CALLBACK_PATH = 'dsp/oauth/callback';

    protected $table = 'oauth_config';

    protected $fillable = ['service_id', 'default_role', 'client_id', 'client_secret', 'redirect_url'];

    protected $encrypted = ['client_secret'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo( 'DreamFactory\Rave\Models\Service', 'service_id', 'id' );
    }

    /**
     * {@inheritdoc}
     */
    public static function setConfig( $id, $config )
    {
        if(null === ArrayUtils::get($config, 'redirect_url'))
        {
            ArrayUtils::set($config, 'redirect_url', 'foo');
        }
        parent::setConfig($id, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute( $key, $value )
    {
        if('redirect_url' === $key)
        {
            $service = $this->service()->first();
            $serviceName = $service->name;
            $value = static::generateRedirectUrl($serviceName);
        }

        parent::setAttribute( $key, $value );
    }

    /**
     * Generates OAuth redirect url based on service name and returns it.
     *
     * @param string $serviceName
     *
     * @return string
     */
    public static function generateRedirectUrl($serviceName)
    {
        $host = \Request::getSchemeAndHttpHost();
        $url = $host.'/'.static::CALLBACK_PATH.'/'.$serviceName;

        return $url;
    }
}