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
use DreamFactory\Core\Models\User;
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
     * Creates a DreamFactory service account user and returns a DreamFactory session token
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
            // Get Azure AD access token
            $tokenData = $this->provider->getAccessToken();

            // Cache the Azure AD token
            if (isset($tokenData['expires_in'])) {
                $cacheKey = 'azure_ad_cc_token_' . $this->id;
                $expiresIn = $tokenData['expires_in'] - 60; // Subtract 60 seconds for safety
                \Cache::put($cacheKey, $tokenData, $expiresIn);
            }

            // Create or retrieve service account user for this OAuth service
            $user = $this->getOrCreateServiceAccountUser();

            // Update last login date
            $user->last_login_date = Carbon::now()->toDateTimeString();
            $user->save();

            // Create DreamFactory session with JWT token
            Session::setUserInfoWithJWT($user);
            $response = Session::getPublicInfo();

            // Include the Azure AD access token for calling external Azure APIs
            $response['azure_access_token'] = $tokenData['access_token'];
            $response['azure_token_type'] = $tokenData['token_type'] ?? 'Bearer';
            $response['azure_expires_in'] = $tokenData['expires_in'] ?? 3600;
            $response['azure_scope'] = $tokenData['scope'] ?? $this->scopes;
            $response['azure_acquired_at'] = Carbon::now()->toIso8601String();

            Log::info('Azure AD Client Credentials session created', [
                'service_id' => $this->id,
                'user_id' => $user->id,
                'tenant_id' => $this->tenantId
            ]);

            return $response;

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
     * Get or create a service account user for Client Credentials flow
     *
     * @return User
     * @throws \Exception
     */
    protected function getOrCreateServiceAccountUser()
    {
        // Create a unique email for this service's service account
        $email = 'cc_' . $this->name . '_' . $this->id . '@service.local';

        // Try to find existing service account user
        $user = User::whereEmail($email)->first();

        if (empty($user)) {
            // Create new service account user
            $data = [
                'username' => $email,
                'name' => 'Service Account for ' . ($this->label ?: $this->name),
                'first_name' => 'Service',
                'last_name' => 'Account (' . $this->name . ')',
                'email' => $email,
                'is_active' => true,
                'oauth_provider' => static::PROVIDER_NAME . '_client_credentials',
            ];

            $user = User::create($data);

            Log::info('Created service account user for Azure AD Client Credentials', [
                'user_id' => $user->id,
                'email' => $email,
                'service_id' => $this->id
            ]);
        }

        // Apply default role if configured
        if (!empty($defaultRole = $this->getDefaultRole())) {
            User::applyDefaultUserAppRole($user, $defaultRole);

            Log::info('Applied default role to service account user', [
                'user_id' => $user->id,
                'role_id' => $defaultRole,
                'service_id' => $this->id
            ]);
        }

        // Apply service-specific role mapping if configured
        if (!empty($serviceId = $this->getServiceId())) {
            User::applyAppRoleMapByService($user, $serviceId);
        }

        return $user;
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