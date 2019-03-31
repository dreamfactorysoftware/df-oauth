<?php

namespace DreamFactory\Core\OAuth\Services;

use Carbon\Carbon;
use DreamFactory\Core\Models\User;
use DreamFactory\Core\OAuth\Components\DfOAuthOneProvider;
use DreamFactory\Core\OAuth\Components\DfOAuthTwoProvider;
use DreamFactory\Core\OAuth\Models\OAuthTokenMap;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Enums\Verbs;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as OAuthUserContract;
use Symfony\Component\HttpFoundation\RedirectResponse;
use DreamFactory\Core\OAuth\Resources\SSO;
use DreamFactory\Core\Exceptions\InternalServerErrorException;

abstract class BaseOAuthService extends BaseRestService
{
    const CACHE_KEY_PREFIX = 'oauth_';

    /** @type array Service Resources */
    protected static $resources = [
        SSO::RESOURCE_NAME => [
            'name'       => SSO::RESOURCE_NAME,
            'class_name' => SSO::class,
            'label'      => 'Single Sign On'
        ],
    ];

    /**
     * OAuth service provider.
     *
     * @var Provider
     */
    protected $provider;
    /**
     * Default role id configured for this OAuth service.
     *
     * @var integer
     */
    protected $defaultRole;

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

        $config = array_get($settings, 'config');
        $this->defaultRole = array_get($config, 'default_role');
        $this->setProvider($config);
    }

    /**
     * Sets the OAuth service provider.
     *
     * @param array $config
     *
     * @return mixed
     */
    abstract protected function setProvider($config);

    /**
     * Returns the OAuth provider name.
     *
     * @return string
     */
    abstract public function getProviderName();

    /**
     * Returns the OAuth service provider.
     *
     * @return Provider
     */
    public function getProvider()
    {
        return $this->provider;
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

    /**
     * Handles login using this service.
     *
     * @param Request $request
     *
     * @return array|bool|RedirectResponse
     */
    public function handleLogin($request)
    {
        /** @var RedirectResponse $response */
        $response = $this->provider->redirect();
        $traitsUsed = class_uses($this->provider);
        $traitTwo = DfOAuthTwoProvider::class;
        $traitOne = DfOAuthOneProvider::class;
        if (isset($traitsUsed[$traitTwo])) {
            $state = $this->provider->getState();
            if (!empty($state)) {
                $key = static::CACHE_KEY_PREFIX . $state;
                \Cache::put($key, $this->getName(), 3);
            }
        } elseif (isset($traitsUsed[$traitOne])) {
            $token = $this->provider->getOAuthToken();
            if (!empty($token)) {
                $key = static::CACHE_KEY_PREFIX . $token;
                \Cache::put($key, $this->getName(), 3);
            }
        }

        if (!$request->ajax()) {
            return $response;
        }
        $url = $response->getTargetUrl();
        $result = ['response' => ['redirect' => true, 'url' => $url]];

        return $result;
    }

    /**
     * Handles OAuth callback
     *
     * @return array
     */
    public function handleOAuthCallback()
    {
        $provider = $this->getProvider();
        /** @var OAuthUserContract $user */
        $user = $provider->user();

        return $this->loginOAuthUser($user);
    }

    /**
     * Logs in OAuth user
     *
     * @param \Laravel\Socialite\Contracts\User $user
     *
     * @return array
     */
    public function loginOAuthUser(OAuthUserContract $user)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $responseBody = $user->accessTokenResponseBody;
        /** @noinspection PhpUndefinedFieldInspection */
        $token = $user->token;

        $dfUser = $this->createShadowOAuthUser($user);
        $dfUser->last_login_date = Carbon::now()->toDateTimeString();
        $dfUser->confirm_code = null;
        $dfUser->save();

        $map = OAuthTokenMap::whereServiceId($this->id)->whereUserId($dfUser->id)->first();
        if (empty($map)) {
            OAuthTokenMap::create(
                [
                    'user_id'    => $dfUser->id,
                    'service_id' => $this->id,
                    'token'      => $token,
                    'response'   => $responseBody
                ]);
        } else {
            $map->update(['token' => $token, 'response' => $responseBody]);
        }
        Session::setUserInfoWithJWT($dfUser);
        $response = Session::getPublicInfo();
        $response['oauth_token'] = $token;
        if (isset($responseBody['id_token'])) {
            $response['id_token'] = $responseBody['id_token'];
        }

        return $response;
    }

    /**
     * If does not exists, creates a shadow OAuth user using user info provided
     * by the OAuth service provider and assigns default role to this user
     * for all apps in the system. If user already exists then updates user's
     * role for all apps and returns it.
     *
     * @param OAuthUserContract $OAuthUser
     *
     * @return User
     * @throws \Exception
     */
    public function createShadowOAuthUser(OAuthUserContract $OAuthUser)
    {
        $fullName = $OAuthUser->getName();
        @list($firstName, $lastName) = explode(' ', $fullName);

        $email = $OAuthUser->getEmail();
        $serviceName = $this->getName();
        $providerName = $this->getProviderName();

        if (empty($email)) {
            $email = $OAuthUser->getId() . '+' . $serviceName . '@' . $serviceName . '.com';
        } 
        // else {
        //     list($emailId, $domain) = explode('@', $email);
        //     $email = $emailId . '+' . $serviceName . '@' . $domain;
        // }

        $user = User::whereEmail($email)->first();

        if (empty($user)) {
            $data = [
                'username'       => $email,
                'name'           => $fullName,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'email'          => $email,
                'is_active'      => true,
                'oauth_provider' => $providerName,
            ];

            $user = User::create($data);
        }

        // todo Should this be done only if the user was not already there?
        if (!empty($defaultRole = $this->getDefaultRole())) {
            User::applyDefaultUserAppRole($user, $defaultRole);
        }
        if (!empty($serviceId = $this->getServiceId())) {
            User::applyAppRoleMapByService($user, $serviceId);
        }

        return $user;
    }

    protected function getOAuthToken()
    {
        return OAuthTokenMap::getCachedToken($this->id, Session::getCurrentUserId());
    }

    protected function getOAuthResponse()
    {
        return OAuthTokenMap::whereServiceId($this->id)->whereUserId(Session::getCurrentUserId())->value('response');
    }
}