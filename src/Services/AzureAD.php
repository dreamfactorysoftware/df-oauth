<?php

namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\OAuth\Components\AzureADClientCredentialsProvider;
use DreamFactory\Core\OAuth\Components\AzureADProvider;
use DreamFactory\Core\OAuth\Resources\ClientCredentials;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Enums\ApiDocFormatTypes;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class AzureAD
 *
 * Azure Active Directory OAuth service supporting both:
 * - Authorization Code flow (user authentication)
 * - Client Credentials flow (service-to-service authentication)
 *
 * @package DreamFactory\Core\OAuth\Services
 */
class AzureAD extends BaseOAuthService
{
    /**
     * Service type identifier
     */
    const SERVICE_TYPE = 'oauth_azure_ad';

    /**
     * Provider name
     */
    const PROVIDER_NAME = 'azure_ad';

    /**
     * @var bool Whether Client Credentials flow is enabled
     */
    protected $isClientCredentials = false;

    /**
     * @var string Azure AD Tenant ID
     */
    protected $tenantId;

    /**
     * @var string Authority URL for token endpoint
     */
    protected $authorityUrl;

    /**
     * @var string Scopes for token request
     */
    protected $scopes;

    /**
     * @var string Grant type (authorization_code or client_credentials)
     */
    protected $grantType;

    /**
     * @var string Client ID
     */
    protected $clientId;

    /**
     * @var string Client Secret
     */
    protected $clientSecret;

    /**
     * Override to add Client Credentials resource
     */
    protected static $resources = [
        ClientCredentials::RESOURCE_NAME => [
            'name'       => ClientCredentials::RESOURCE_NAME,
            'class_name' => ClientCredentials::class,
            'label'      => 'Client Credentials Token'
        ],
        'sso' => [
            'name'       => 'sso',
            'class_name' => 'DreamFactory\Core\OAuth\Resources\SSO',
            'label'      => 'Single Sign On'
        ],
    ];

    /**
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $config = Arr::get($settings, 'config', []);

        // Extract Azure AD specific configuration
        $this->isClientCredentials = (bool) Arr::get($config, 'is_client_credentials', false);
        $this->tenantId = Arr::get($config, 'tenant_id');
        $this->authorityUrl = Arr::get($config, 'authority_url');
        $this->scopes = Arr::get($config, 'scopes');
        $this->grantType = Arr::get($config, 'grant_type', 'authorization_code');
        $this->clientId = Arr::get($config, 'client_id');
        $this->clientSecret = Arr::get($config, 'client_secret');

        parent::__construct($settings);
    }

    /**
     * Sets the OAuth service provider based on configuration
     *
     * @param array $config
     * @return mixed|void
     * @throws InternalServerErrorException
     */
    protected function setProvider($config)
    {
        if (!isset($config['client_id']) || !isset($config['client_secret'])) {
            throw new InternalServerErrorException('Azure AD OAuth service is not configured properly. Client ID and Client Secret are required.');
        }

        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];
        $redirectUrl = Arr::get($config, 'redirect_url');
        $tenantId = Arr::get($config, 'tenant_id', 'common');
        $authorityUrl = Arr::get($config, 'authority_url');
        $scopes = Arr::get($config, 'scopes', 'openid profile email');
        $isClientCredentials = (bool) Arr::get($config, 'is_client_credentials', false);
        $grantType = Arr::get($config, 'grant_type', 'authorization_code');

        // Determine which provider to use based on grant type
        if ($isClientCredentials || $grantType === 'client_credentials') {
            // Use Client Credentials provider
            $this->provider = new AzureADClientCredentialsProvider(
                $clientId,
                $clientSecret,
                $tenantId,
                $authorityUrl,
                $scopes
            );
        } else {
            // Use standard Authorization Code provider
            if (empty($redirectUrl)) {
                throw new InternalServerErrorException('Azure AD OAuth service requires a redirect URL for Authorization Code flow.');
            }

            $this->provider = new AzureADProvider(
                $clientId,
                $clientSecret,
                $redirectUrl,
                $tenantId,
                $authorityUrl,
                $scopes
            );
        }
    }

    /**
     * Returns the OAuth provider name
     *
     * @return string
     */
    public function getProviderName()
    {
        return static::PROVIDER_NAME;
    }

    /**
     * Get an access token using Client Credentials flow
     *
     * @return array
     * @throws BadRequestException
     * @throws UnauthorizedException
     */
    public function getClientCredentialsToken()
    {
        if (!$this->isClientCredentials) {
            throw new BadRequestException('Client Credentials flow is not enabled for this service.');
        }

        if (!$this->provider instanceof AzureADClientCredentialsProvider) {
            throw new InternalServerErrorException('Provider is not configured for Client Credentials flow.');
        }

        try {
            $tokenData = $this->provider->getAccessToken();

            // Cache the token
            if (isset($tokenData['expires_in'])) {
                $cacheKey = 'azure_ad_cc_token_' . $this->id;
                $expiresIn = $tokenData['expires_in'] - 60; // Subtract 60 seconds for safety
                \Cache::put($cacheKey, $tokenData, $expiresIn);
            }

            // Return token data
            return [
                'access_token' => $tokenData['access_token'],
                'token_type' => $tokenData['token_type'] ?? 'Bearer',
                'expires_in' => $tokenData['expires_in'] ?? 3600,
                'scope' => $tokenData['scope'] ?? $this->scopes,
                'acquired_at' => Carbon::now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error('Azure AD Client Credentials token request failed:', [
                'error' => $e->getMessage(),
                'service_id' => $this->id,
                'tenant_id' => $this->tenantId
            ]);

            throw new UnauthorizedException('Failed to obtain access token: ' . $e->getMessage());
        }
    }

    /**
     * Refresh an existing access token (Client Credentials flow doesn't support refresh)
     * For Client Credentials, we simply get a new token
     *
     * @return array
     * @throws BadRequestException
     */
    public function refreshClientCredentialsToken()
    {
        if (!$this->isClientCredentials) {
            throw new BadRequestException('This method is only available for Client Credentials flow.');
        }

        // Client Credentials flow doesn't have refresh tokens
        // We simply request a new access token
        return $this->getClientCredentialsToken();
    }

    /**
     * Validate the current cached token
     *
     * @return array
     */
    public function validateToken()
    {
        $cacheKey = 'azure_ad_cc_token_' . $this->id;
        $cachedToken = \Cache::get($cacheKey);

        if ($cachedToken) {
            return [
                'valid' => true,
                'cached' => true,
                'token_type' => $cachedToken['token_type'] ?? 'Bearer',
                'scope' => $cachedToken['scope'] ?? $this->scopes,
            ];
        }

        return [
            'valid' => false,
            'cached' => false,
            'message' => 'No valid token in cache. Please request a new token.',
        ];
    }

    /**
     * Clear cached token
     *
     * @return array
     */
    public function clearToken()
    {
        $cacheKey = 'azure_ad_cc_token_' . $this->id;
        \Cache::forget($cacheKey);

        return [
            'success' => true,
            'message' => 'Token cache cleared successfully.',
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getApiDocInfo()
    {
        $base = parent::getApiDocInfo();

        if ($this->isClientCredentials) {
            $base['paths']['/client_credentials'] = [
                'get' => [
                    'summary' => 'Get access token using Client Credentials',
                    'description' => 'Obtains an access token from Azure AD using Client Credentials flow (service-to-service authentication)',
                    'operationId' => 'getClientCredentialsToken' . $this->name,
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'access_token' => ['type' => 'string'],
                                            'token_type' => ['type' => 'string'],
                                            'expires_in' => ['type' => 'integer'],
                                            'scope' => ['type' => 'string'],
                                            'acquired_at' => ['type' => 'string'],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'post' => [
                    'summary' => 'Refresh access token',
                    'description' => 'Gets a new access token (Client Credentials flow does not support refresh tokens)',
                    'operationId' => 'refreshClientCredentialsToken' . $this->name,
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'access_token' => ['type' => 'string'],
                                            'token_type' => ['type' => 'string'],
                                            'expires_in' => ['type' => 'integer'],
                                            'scope' => ['type' => 'string'],
                                            'acquired_at' => ['type' => 'string'],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'delete' => [
                    'summary' => 'Clear cached token',
                    'description' => 'Removes the cached access token',
                    'operationId' => 'clearToken' . $this->name,
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'message' => ['type' => 'string'],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $base;
    }
}