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
    public static function getApiDocInfo($service, array $resource = [])
    {
        $base = parent::getApiDocInfo($service, $resource);
        $serviceName = strtolower($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;
        unset($base['paths'][$path]['get']);
        $base['paths'][$path]['post'] = [
            'tags'        => [$serviceName],
            'summary'     => 'performSSO() - Single Sign On',
            'operationId' => 'performSSO',
            'consumes'    => ['application/json', 'application/xml'],
            'produces'    => ['application/json', 'application/xml'],
            'description' => 'Performs Single Sign On using OAuth 2.0 access token',
            'parameters'  => [
                [
                    'name'        => 'body',
                    'description' => 'Content - OAuth token response',
                    'schema'      => [
                        'type'       => 'object',
                        'properties' => [
                            'access_token'  => [
                                'type'        => 'string',
                                'description' => '(Required) The access token issued by the authorization server.'
                            ],
                            'token_type'    => [
                                'type'        => 'string',
                                'description' => '(Required) The type of the token. Typically Bearer.'
                            ],
                            'expires_in'    => [
                                'type'        => 'integer',
                                'description' => '(Recommended) The lifetime in seconds of the access token.'
                            ],
                            'refresh_token' => [
                                'type'        => 'string',
                                'description' => '(Optional) The refresh token, which can be used to obtain new access tokens.'
                            ],
                            'scope'         => [
                                'type'        => 'string',
                                'description' => 'OPTIONAL, if identical to the scope requested by the client; otherwise, REQUIRED.'
                            ],
                            'id_token'      => [
                                'type'        => 'string',
                                'description' => 'User identification token. Required for OpenID Connect only.'
                            ],
                        ]
                    ],
                    'in'          => 'body',
                    'required'    => true
                ]
            ],
            'responses'   => [
                '200'     => [
                    'description' => 'Successful login',
                    'schema'      => [
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
                            'token_response'  => [
                                'type'       => 'object',
                                'properties' => [
                                    'access_token'  => ['type' => 'string'],
                                    'token_type'    => ['type' => 'string'],
                                    'expires_in'    => ['type' => 'string'],
                                    'refresh_token' => ['type' => 'string'],
                                    'scope'         => ['type' => 'string'],
                                    'id_token'      => ['type' => 'string'],
                                ]
                            ],
                        ]
                    ]
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
        ];

        return $base;
    }
}