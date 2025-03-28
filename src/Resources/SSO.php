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
        $code = $this->request->input('code');

        if (!$code) {
            Log::error('Missing OAuth code in callback');
            return response()->json(['error' => 'OAuth code missing'], 400);
        }

        /** @var $provider \DreamFactory\Core\OAuth\Components\DfOAuthTwoProvider */
        $provider = $this->getParent()->getProvider();

        try {
            Log::debug('Exchanging code for access token...');
            $tokenResponse = $provider->getAccessTokenResponse($code);
            Log::debug('Access Token Response:', $tokenResponse);

            if (!isset($tokenResponse['access_token'])) {
                throw new \Exception('Access token missing in response');
            }

            $accessToken = $tokenResponse['access_token'];
            Log::debug("OAuth Access Token: $accessToken");

            $user = $provider->getUserFromTokenResponse($tokenResponse);
            Log::debug('OAuth User Object:', [
                'name' => $user->getName(),
                'nickname' => $user->getNickname(),
                'email' => $user->getEmail(),
            ]);

            $result = $this->getParent()->loginOAuthUser($user);
            return $this->respond()
                ->setStatusCode(302)
                ->setHeaders([
                    'Location' => "/dreamfactory/dist/index.html#/auth/login?jwt=" . urlencode($result['session_token'] ?? ''),
                ])
                ->setContent(json_encode($result));
        } catch (\Exception $e) {
            Log::error('OAuth callback failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'OAuth callback failed'], 500);
        }
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