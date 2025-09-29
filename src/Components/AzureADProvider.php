<?php

namespace DreamFactory\Core\OAuth\Components;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Arr;

/**
 * Azure AD OAuth Provider for Authorization Code flow
 *
 * Implements OAuth 2.0 Authorization Code flow for Azure Active Directory.
 * This provider enables user authentication with Azure AD.
 */
class AzureADProvider extends AbstractProvider implements ProviderInterface
{
    use DfOAuthTwoProvider;

    /**
     * @var string Azure AD tenant ID
     */
    protected $tenantId;

    /**
     * @var string Authority URL
     */
    protected $authorityUrl;

    /**
     * @var string Default scopes
     */
    protected $defaultScopes;

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'profile', 'email', 'offline_access'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Constructor
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUrl
     * @param string $tenantId
     * @param string|null $authorityUrl
     * @param string|null $scopes
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUrl,
        string $tenantId = 'common',
        ?string $authorityUrl = null,
        ?string $scopes = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUrl = $redirectUrl;
        $this->tenantId = $tenantId;

        // Set authority URL
        if (empty($authorityUrl)) {
            $this->authorityUrl = "https://login.microsoftonline.com/{$tenantId}";
        } else {
            $this->authorityUrl = str_replace('{tenant_id}', $tenantId, $authorityUrl);
        }

        // Set scopes
        if (!empty($scopes)) {
            $this->defaultScopes = $scopes;
            $this->scopes = explode(' ', $scopes);
        }

        // Initialize Guzzle HTTP client
        $this->httpClient = new \GuzzleHttp\Client();
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->authorityUrl . '/oauth2/v2.0/authorize',
            $state
        );
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->authorityUrl . '/oauth2/v2.0/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://graph.microsoft.com/v1.0/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'userPrincipalName'),
            'name' => Arr::get($user, 'displayName'),
            'email' => Arr::get($user, 'mail') ?: Arr::get($user, 'userPrincipalName'),
            'avatar' => null,
        ]);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
            'grant_type' => 'authorization_code',
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
        ];
    }

    /**
     * Format the given scopes.
     *
     * @param array $scopes
     * @param string $scopeSeparator
     * @return string
     */
    protected function formatScopes(array $scopes, $scopeSeparator)
    {
        return implode($scopeSeparator, $scopes);
    }

    /**
     * Get the tenant ID
     *
     * @return string
     */
    public function getTenantId()
    {
        return $this->tenantId;
    }

    /**
     * Get the authority URL
     *
     * @return string
     */
    public function getAuthorityUrl()
    {
        return $this->authorityUrl;
    }

    /**
     * Set additional request parameters
     *
     * @param array $parameters
     * @return $this
     */
    public function with(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }
}