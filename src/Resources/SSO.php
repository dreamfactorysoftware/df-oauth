<?php

namespace DreamFactory\Core\OAuth\Resources;

use DreamFactory\Core\Resources\BaseRestResource;
use Log;

class SSO extends BaseRestResource
{
    /** Resource name */
    const RESOURCE_NAME = 'sso';

    /** Resource Identifier */
    const RESOURCE_IDENTIFIER = 'name';

    /**
     * {@inheritdoc}
     */
    protected static function getResourceIdentifier()
    {
        return static::RESOURCE_IDENTIFIER;
    }

    /** {@inheritdoc} */
    protected function handleGET()
    {
        // Handle OAuth callback (code + state from OAuth provider redirect)
        return $this->getParent()->handleOAuthCallback();
    }

    /** {@inheritdoc} */
    protected function handlePOST()
    {
        $code = $this->request->input('code');

        if (!$code) {
            Log::error('Missing OAuth code in callback');

            // Redirect to login page with error message instead of returning JSON
            return $this->respond()
                ->setStatusCode(302)
                ->setHeaders([
                    'Location' => "/dreamfactory/dist/?error=" . urlencode('OAuth authorization was denied or failed'),
                ])
                ->setContent('');
        }

        /** @var $provider \DreamFactory\Core\OAuth\Components\DfOAuthTwoProvider */
        $provider = $this->getParent()->getProvider();

        try {
            Log::info('Processing OAuth token exchange');
            $tokenResponse = $provider->getAccessTokenResponse($code);

            if (!isset($tokenResponse['access_token'])) {
                throw new \Exception('Access token missing in response');
            }

            $user = $provider->getUserFromTokenResponse($tokenResponse);
            Log::info('OAuth user retrieved successfully', [
                'provider' => $this->getParent()->getProviderName(),
                'user_id' => $user->getId() ?? 'unknown'
            ]);

            $result = $this->getParent()->loginOAuthUser($user);
            // Check if we're in development mode for JWT redirect too
            $baseUrl = $this->getRedirectBaseUrl();
            $jwtRedirectUrl = $baseUrl . "?jwt=" . urlencode($result['session_token'] ?? '');

            return $this->respond()
                ->setStatusCode(302)
                ->setHeaders([
                    'Location' => $jwtRedirectUrl,
                ])
                ->setContent(json_encode($result));
        } catch (\Exception $e) {
            Log::error('OAuth callback failed:', ['error' => $e->getMessage()]);

            // For OAuth callbacks, we need to redirect to an error page instead of returning JSON
            $errorMessage = urlencode($e->getMessage());

            // Check if we're in development mode (detect by checking if port 4200 is accessible)
            $baseUrl = $this->getRedirectBaseUrl();
            $redirectUrl = $baseUrl . "?error=" . $errorMessage;
            Log::info('OAuth error redirect URL:', ['url' => $redirectUrl]);

            return $this->respond()
                ->setStatusCode(302)
                ->setHeaders([
                    'Location' => $redirectUrl,
                ])
                ->setContent('');
        }
    }

    /**
     * Get the appropriate redirect base URL based on environment
     */
    private function getRedirectBaseUrl()
    {
        // Check for custom OAuth redirect URL first
        $customUrl = env('OAUTH_REDIRECT_URL');
        if ($customUrl) {
            Log::info('Using custom OAuth redirect URL', ['url' => $customUrl]);
            return $customUrl;
        }

        // Fall back to production URL
        $prodUrl = env('OAUTH_DEFAULT_REDIRECT_PATH', '/dreamfactory/dist/');
        Log::info('Using default production URL', ['url' => $prodUrl]);
        return $prodUrl;
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $service = $this->getServiceName();
        $capitalized = camelize($service);

        $base = [
            $path => [
                'get' => [
                    'summary'     => 'Single Sign On',
                    'description' => 'Performs Single Sign On using OAuth 2.0 access token',
                    'operationId' => 'perform' . $capitalized . 'SSO',
                    'requestBody' => [
                        'description' => 'Content - OAuth token response',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'required'   => ['access_token', 'token_type'],
                                    'properties' => [
                                        'access_token'  => [
                                            'type'        => 'string',
                                            'description' => 'The access token issued by the authorization server.'
                                        ],
                                        'token_type'    => [
                                            'type'        => 'string',
                                            'description' => 'The type of the token. Typically Bearer.'
                                        ],
                                        'expires_in'    => [
                                            'type'        => 'integer',
                                            'description' => 'The lifetime in seconds of the access token.'
                                        ],
                                        'refresh_token' => [
                                            'type'        => 'string',
                                            'description' => 'The refresh token, which can be used to obtain new access tokens.'
                                        ],
                                        'scope'         => [
                                            'type'        => 'string',
                                            'description' => 'OPTIONAL, if identical to the scope requested by the client; otherwise, REQUIRED.'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'required'    => true
                    ],
                    'responses'   => [
                        '200' => [
                            'description' => 'Successful login',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'session_token'   => ['type' => 'string'],
                                            'session_id'      => ['type' => 'string'],
                                            'id'              => ['type' => 'integer'],
                                            'name'            => ['type' => 'string'],
                                            'first_name'      => ['type' => 'string'],
                                            'last_name'       => ['type' => 'string'],
                                            'email'           => ['type' => 'string'],
                                            'is_sys_admin'    => ['type' => 'string'],
                                            'last_login_date' => ['type' => 'string'],
                                            'host'            => ['type' => 'string'],
                                            'oauth_token'     => ['type' => 'string'],
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];

        return $base;
    }
}