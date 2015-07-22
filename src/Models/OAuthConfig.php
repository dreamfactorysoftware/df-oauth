<?php
namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Contracts\ServiceConfigHandlerInterface;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\Service;

/**
 * Class OAuthConfig
 *
 * @package DreamFactory\Core\OAuth\Models
 */
class OAuthConfig extends BaseServiceConfigModel implements ServiceConfigHandlerInterface
{
    protected $table = 'oauth_config';

    protected $fillable = ['service_id', 'default_role', 'client_id', 'client_secret', 'redirect_url', 'icon_class'];

    protected $encrypted = ['client_secret'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * @param array     $config
     * @param bool|true $create
     *
     * @return bool
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    public static function validateConfig($config, $create = true)
    {
        $validator = static::makeValidator($config, [
            'default_role'  => 'required',
            'client_id'     => 'required',
            'client_secret' => 'required',
            'redirect_url'  => 'required'
        ], $create);

        if ($validator->fails()) {
            $messages = $validator->messages()->getMessages();
            throw new BadRequestException('Validation failed.', null, null, $messages);
        }

        return true;
    }
}