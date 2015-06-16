<?php
 namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Models\OAuthConfig;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Services\BaseRestService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Laravel\Socialite\Contracts\Provider;

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
     * Handles POST request on this service.
     *
     * @return array|bool|RedirectResponse
     */
    protected function handlePOST()
    {
        if ('session' === $this->resource) {
            /** @var RedirectResponse $response */
            $response = $this->driver->redirect();
            $url = $response->getTargetUrl();

            /** @var \Request $request */
            $request = $this->request->getDriver();

            if ($request->ajax()) {
                $result = ['response' => ['login_url' => $url]];

                return $result;
            } else {
                return $response;
            }
        }

        return false;
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
}