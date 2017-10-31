<?php

namespace DreamFactory\Core\OAuth\Resources;

use DreamFactory\Core\Resources\BaseRestResource;

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
    protected function handlePOST()
    {
        $payload = $this->request->getPayloadData();
        /** @var $provider \DreamFactory\Core\OAuth\Components\DfOAuthTwoProvider */
        $provider = $this->getParent()->getProvider();
        $user = $provider->getUserFromTokenResponse($payload);

        return $this->getParent()->loginOAuthUser($user);
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $service = $this->getServiceName();
        $capitalized = camelize($service);
        $class = trim(strrchr(static::class, '\\'), '\\');

        $base = [
            $path => [
                'post' => [
                    'summary'     => 'perform' . $capitalized . $class . 'SSO() - Single Sign On',
                    'operationId' => 'perform' . $capitalized . $class . 'SSO',
                    'description' => 'Performs Single Sign On using OAuth 2.0 access token',
                    'requestBody' => [
                        'description' => 'Content - OAuth token response',
                        'schema'      => [
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
                            ]
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