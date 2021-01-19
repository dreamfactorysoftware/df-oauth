<?php

namespace DreamFactory\Core\OAuth\Services;

use Carbon\Carbon;
use DreamFactory\Core\Exceptions\ForbiddenException;
use DreamFactory\Core\Models\User;
use DreamFactory\Core\OAuth\Models\HerokuAddonSecretType;
use DreamFactory\Core\OAuth\Models\HerokuAddonUser;
use DreamFactory\Core\OAuth\Resources\HerokuAddonSSO;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\Utility\JWTUtilities;
use Illuminate\Support\Facades\Log;

class HerokuAddonSSOService extends BaseRestService
{
    const CACHE_KEY_PREFIX = 'heroku_addon_sso_';

    /** @type array Service Resources */
    protected static $resources = [
        HerokuAddonSSO::RESOURCE_NAME => [
            'name'       => HerokuAddonSSO::RESOURCE_NAME,
            'class_name' => HerokuAddonSSO::class,
            'label'      => 'Single Sign On'
        ],
    ];

    /**
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $settings = (array)$settings;
        $settings['verbAliases'] = [
            Verbs::PUT => Verbs::POST,
        ];

        parent::__construct($settings);
    }

    /**
     * @param $payload
     * @return array
     * @throws ForbiddenException
     */
    public function handleSSORequest($payload)
    {
        $this->checkToken($payload['resource_token'], $payload['timestamp'], $payload['resource_id']);
        $this->checkTimestamp($payload['timestamp']);

        $user = $this->getOrCreateUser($payload['user_id'], $payload['email']);

        return [
            'id' => $user->id,
            'email' => $user->email,
            'jwt' => JWTUtilities::makeJWTByUser($user->id, $user->email, false),
        ];
    }

    /**
     * @param $herokuUserId
     * @param $email
     * @return User
     */
    public function getOrCreateUser($herokuUserId, $email)
    {
        $herokuAddonUser = HerokuAddonUser::query()->where('heroku_user_id', '=', $herokuUserId)->firstOr(['*'], function () use ($email, $herokuUserId) {
            $user = new User([
                'name' => $email,
                'email' => $email,
                'is_active' => 1,
            ]);
            $user->is_sys_admin = true;
            $user->save();
            $herokuAddonUser = new HerokuAddonUser([
                'user_id' => $user->id,
                'heroku_user_id' => $herokuUserId,
            ]);
            $herokuAddonUser->save();
            return $herokuAddonUser;
        });
        /** @var User $user */
        $user = User::query()->where('id', '=', $herokuAddonUser->user_id)->firstOrFail();
        return $user;
    }

    /**
     * According to <a href="https://devcenter.heroku.com/articles/add-on-single-sign-on#signing-in-the-user-on-redirect">Signing in the user on redirect</a>.
     * If the SHA1 hash you compute does not match the one passed in resource_token (or token if you’re using v1),
     * the user should be shown a page with an HTTP status code of 403.
     *
     * @param $token
     * @param $timestamp
     * @param $resourceId
     * @throws ForbiddenException
     */
    private function checkToken($token, $timestamp, $resourceId)
    {
        /** @var HerokuAddonSSOService $service */
        $secretType = $this->getConfig('secret_type');
        switch ($secretType) {
            case HerokuAddonSecretType::FILE: {
                $secret = file_get_contents((string)$this->getConfig('secret'));
                break;
            }
            case HerokuAddonSecretType::ENVIRONMENT: {
                $secret = env((string)$this->getConfig('secret'));
                break;
            }
            case HerokuAddonSecretType::STRING: {
                $secret = $this->getConfig('secret');
                break;
            }
            default: {
                Log::error("Unsupported Heroku Add-on secret type: ${secretType}. Use " . HerokuAddonSecretType::STRING . " behavior.");
                $secret = $this->getConfig('secret');
            }
        }
        if (sha1("{$resourceId}:{$secret}:{$timestamp}") !== $token) {
            throw new ForbiddenException('Token invalid. Please provide valid token');
        }
    }

    /**
     * According to <a href="https://devcenter.heroku.com/articles/add-on-single-sign-on#signing-in-the-user-on-redirect">Signing in the user on redirect</a>.
     * If the timestamp is older than five minutes, they should also see a 403.
     *
     * @param $timestamp
     * @throws ForbiddenException
     */
    private function checkTimestamp($timestamp)
    {
        $unixTimestamp = intval($timestamp);
        $tokenExpirationTime = 5;
        if ($tokenExpirationTime !== 5) {
            Log::error('df-oauth//src/Services/HerokuAddonSSOService::checkTimestamp - token expiration time not 5 minutes');
        }
        if (!Carbon::createFromTimestamp($unixTimestamp)->addMinutes($tokenExpirationTime)->isAfter(Carbon::now())) {
            throw new ForbiddenException('Timestamp expired. Please login again');
        }
    }
}
