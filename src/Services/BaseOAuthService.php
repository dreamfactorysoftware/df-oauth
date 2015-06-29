<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\Models\User;
use DreamFactory\Core\OAuth\Models\OAuthConfig;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Services\BaseRestService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as OAuthUserContract;

abstract class BaseOAuthService extends BaseRestService
{
    /**
     * Callback handler url
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * OAuth service provider.
     *
     * @var Provider
     */
    protected $driver;

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
        $verbAliases = [
            Verbs::PUT   => Verbs::POST,
            Verbs::MERGE => Verbs::PATCH
        ];
        ArrayUtils::set($settings, "verbAliases", $verbAliases);
        parent::__construct($settings);

        $config = ArrayUtils::get($settings, 'config');
        $this->defaultRole = ArrayUtils::get($config, 'default_role');
        $this->redirectUrl = OAuthConfig::generateRedirectUrl($this->name);
        $this->setDriver($config);
    }

    /**
     * Sets the OAuth service provider.
     *
     * @param array $config
     *
     * @return mixed
     */
    abstract protected function setDriver($config);

    /**
     * Returns the OAuth provider name.
     *
     * @return string
     */
    abstract public function getProviderName();

    /**
     * Handles login using this service.
     *
     * @param \Request $request
     *
     * @return array|bool|RedirectResponse
     */
    public function handleLogin($request)
    {
        /** @var RedirectResponse $response */
        $response = $this->driver->redirect();
        $url = $response->getTargetUrl();

        if ($request->ajax()) {
            $result = ['response' => ['login_url' => $url]];

            return $result;
        } else {
            return $response;
        }
    }

    /**
     * Returns the OAuth service provider.
     *
     * @return Provider
     */
    public function getDriver()
    {
        return $this->driver;
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
        $accessToken = $OAuthUser->token;

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
                'is_active'      => 1,
                'oauth_provider' => $providerName,
                'password'       => $accessToken
            ];

            $user = User::create($data);
        }

        $defaultRole = $this->getDefaultRole();

        User::applyDefaultUserAppRole($user, $defaultRole);

        return $user;
    }
}