<?php

namespace DreamFactory\Core\OAuth\Resources;

use DreamFactory\Core\Resources\BaseRestResource;

class HerokuAddonSSO extends BaseRestResource
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
        $payload = $this->request->getPayloadData();
        $result = $this->getParent()->handleSSORequest($payload);

        return $this->respond()
            ->setStatusCode(302)
            ->setHeaders([
                'Location' => "/?jwt={$result['jwt']}",
            ])
            ->setContent($result);
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $service = $this->getServiceName();
        $capitalized = camelize($service);

        return [
            $path => [
                'get' => [
                    'summary' => 'Single Sign On',
                    'description' => 'Performs Single Sign On using Heroku Add-on API',
                    'operationId' => 'perform' . $capitalized . 'SSO',
                    'requestBody' => [
                        'description' => 'Content - Heroku Add-on SSO request',
                        'content' => [
                            'application/x-www-form-urlencoded' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => [
                                        'resource_token',
                                        'resource_id',
                                        'timestamp',
                                        'email',
                                        'user_id',
                                    ],
                                    'properties' => [
                                        'email' => [
                                            'type' => 'string',
                                        ],
                                        'user_id' => [
                                            'type' => 'string',
                                        ],
                                        'app' => [
                                            'type' => 'string',
                                        ],
                                        'context_app' => [
                                            'type' => 'string',
                                        ],
                                        'timestamp' => [
                                            'type' => 'string',
                                        ],
                                        'resource_id' => [
                                            'type' => 'string',
                                        ],
                                        'resource_token' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'required' => true
                    ],
                    'responses' => [
                        '304' => [
                            'description' => 'Successful login. If you use a proxy, make sure that redirects are disabled for proxy.',
                            'headers' => [
                                'Location' => [
                                    'schema' => [
                                        'type' => 'string',
                                    ],
                                    'description' => 'Login URL',
                                ],
                            ],
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'token' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
