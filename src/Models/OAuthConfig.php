<?php
namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Contracts\ServiceConfigHandlerInterface;
use DreamFactory\Core\Models\BaseServiceConfigModel;

/**
 * Class OAuthConfig
 *
 * @package DreamFactory\Core\OAuth\Models
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
        return $this->belongsTo('DreamFactory\Core\Models\Service', 'service_id', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public static function setConfig($id, $config)
    {
        if (null === ArrayUtils::get($config, 'redirect_url')) {
            ArrayUtils::set($config, 'redirect_url', 'foo');
        }
        parent::setConfig($id, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($key, $value)
    {
        if ('redirect_url' === $key && empty($value)) {
            $service = $this->service()->first();
            $serviceName = $service->name;
            $value = static::generateRedirectUrl($serviceName);
        }

        parent::setAttribute($key, $value);
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
        $url = $host . '/' . static::CALLBACK_PATH . '/' . $serviceName;

        return $url;
    }
}