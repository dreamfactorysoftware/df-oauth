<?php
namespace DreamFactory\Core\OAuth\Components;

use Carbon\Carbon;
use DreamFactory\Core\Models\User;
use DreamFactory\Core\OAuth\Models\OAuthTokenMap;
use DreamFactory\Core\Utility\Session;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as OAuthUserContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait OAuthServiceTrait
{
    /**
     * Service id
     *
     * @var  integer
     */
    protected $id;
    /**
     * Service name
     *
     * @var  string
     */
    protected $name;
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
     * Returns the service name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
        if (!$request->ajax()) {
            return $response;
        }

        $url = $response->getTargetUrl();
        $result = ['response' => ['redirect' => true, 'url' => $url]];

        return $result;
    }

    public function handleOAuthCallback()
    {
        $provider = $this->getProvider();
        /** @var OAuthUserContract $user */
        $user = $provider->user();

        /** @noinspection PhpUndefinedFieldInspection */
        $responseBody = $user->accessTokenResponseBody;
        /** @noinspection PhpUndefinedFieldInspection */
        $token = $user->token;
        \Log::debug('Access Token Response: ' . print_r($responseBody, true));
        \Log::debug("OAuth Access Token: $token");

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
        $session = Session::getPublicInfo();
        $session['oauth_access_token'] = $token;

        return $session;
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
    protected function createShadowOAuthUser(OAuthUserContract $OAuthUser)
    {
        $fullName = $OAuthUser->getName();
        @list($firstName, $lastName) = explode(' ', $fullName);

        $email = $OAuthUser->getEmail();
        $serviceName = $this->getName();
        $providerName = $this->getProviderName();

        if (empty($email)) {
            $email = $OAuthUser->getId() . '+' . $serviceName . '@' . $serviceName . '.com';
        } else {
            list($emailId, $domain) = explode('@', $email);
            $email = $emailId . '+' . $serviceName . '@' . $domain;
        }

        $user = User::whereEmail($email)->first();

        if (empty($user)) {
            $data = [
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

        return $user;
    }

    protected function getOAuthToken()
    {
        return OAuthTokenMap::getCachedToken($this->id, Session::getCurrentUserId());
    }
}