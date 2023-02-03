<?php
namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

/**
 * OAuthTokenMap
 *
 * @property integer $user_id
 * @property integer $service_id
 * @property string  $token
 * @property string  $response
 * @method static Builder|OAuthTokenMap whereUserId($value)
 * @method static Builder|OAuthTokenMap whereServiceId($value)
 * @method static Builder|OAuthTokenMap whereToken($value)
 */
class OAuthTokenMap extends BaseModel
{
    protected $table = 'oauth_token_map';

    protected $fillable = ['user_id', 'service_id', 'token', 'response'];

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'service_id' => 'integer',
        'response'   => 'array',
    ];

    public $timestamps = false;

    /** @inheritdoc */
    public function validate($data, $throwException = true)
    {
        if (parent::validate($data)) {
            $userId = Arr::get($data, 'user_id');
            $serviceId = Arr::get($data, 'service_id');

            if ($userId && $serviceId) {
                $model = $this->whereServiceId($serviceId)->whereUserId($userId)->first();

                if (!empty($model) && $model->id != Arr::get($data, 'id')) {
                    throw new BadRequestException('Only one user-service-token assignment allowed.');
                }
            }

            return true;
        }

        return false;
    }

    public static function boot()
    {
        parent::boot();

        static::saved(
            function (OAuthTokenMap $map) {
                static::setCachedToken($map->service_id, $map->user_id, $map->token);
            }
        );

        static::deleted(
            function (OAuthTokenMap $map) {
                static::setCachedToken($map->service_id, $map->user_id, null);
            }
        );
    }

    public static function getCachedToken($service_id, $user_id)
    {
        $cacheKey = static::makeTokenCacheKey($service_id, $user_id);
        $result = \Cache::remember($cacheKey, \Config::get('df.default_cache_ttl'),
            function () use ($service_id, $user_id) {
                try {
                    return static::whereServiceId($service_id)->whereUserId($user_id)->value('token');
                } catch (ModelNotFoundException $ex) {
                    return null;
                }
            });

        return $result;
    }

    public static function setCachedToken($service_id, $user_id, $token)
    {
        $cacheKey = static::makeTokenCacheKey($service_id, $user_id);
        \Cache::put($cacheKey, $token, \Config::get('df.default_cache_ttl'));
    }

    public static function makeTokenCacheKey($service_id, $user_id)
    {
        return "service-$service_id:user-$user_id:token";
    }
}