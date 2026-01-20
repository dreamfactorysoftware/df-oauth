<?php

namespace DreamFactory\Core\OAuth\Resources;

use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\OAuth\Services\AzureAD;

/**
 * Class ClientCredentials
 *
 * Handles Client Credentials OAuth flow endpoints
 *
 * @package DreamFactory\Core\OAuth\Resources
 */
class ClientCredentials extends BaseRestResource
{
    /**
     * Resource name constant
     */
    const RESOURCE_NAME = 'client_credentials';

    /**
     * @var string Resource name
     */
    protected $name = self::RESOURCE_NAME;

    /**
     * Handles GET requests to obtain access token
     *
     * @return array
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws InternalServerErrorException
     */
    protected function handleGET()
    {
        $service = $this->getService();

        if (!$service instanceof AzureAD) {
            throw new BadRequestException('Client Credentials flow is only supported for Azure AD services.');
        }

        // Get a new access token
        return $service->getClientCredentialsToken();
    }

    /**
     * Handles POST requests to refresh/renew access token
     *
     * @return array
     * @throws BadRequestException
     * @throws UnauthorizedException
     * @throws InternalServerErrorException
     */
    protected function handlePOST()
    {
        $service = $this->getService();

        if (!$service instanceof AzureAD) {
            throw new BadRequestException('Client Credentials flow is only supported for Azure AD services.');
        }

        // Refresh the access token (gets new token for client credentials)
        return $service->refreshClientCredentialsToken();
    }

    /**
     * Handles DELETE requests to clear cached token
     *
     * @return array
     * @throws BadRequestException
     */
    protected function handleDELETE()
    {
        $service = $this->getService();

        if (!$service instanceof AzureAD) {
            throw new BadRequestException('Client Credentials flow is only supported for Azure AD services.');
        }

        // Clear the cached token
        return $service->clearToken();
    }

    /**
     * Get API documentation for this resource
     *
     * @return array
     */
    public function getApiDocInfo()
    {
        $base = parent::getApiDocInfo();

        $base['paths'] = [
            '/' . $this->name => [
                'get' => [
                    'summary' => 'Get access token using Client Credentials',
                    'description' => 'Obtains an access token from Azure AD using Client Credentials flow for service-to-service authentication',
                    'operationId' => 'getClientCredentialsToken',
                    'tags' => ['oauth'],
                    'responses' => [
                        '200' => [
                            'description' => 'Successful token response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'access_token' => [
                                                'type' => 'string',
                                                'description' => 'The access token to use for API calls'
                                            ],
                                            'token_type' => [
                                                'type' => 'string',
                                                'description' => 'Type of token (usually Bearer)'
                                            ],
                                            'expires_in' => [
                                                'type' => 'integer',
                                                'description' => 'Token expiration time in seconds'
                                            ],
                                            'scope' => [
                                                'type' => 'string',
                                                'description' => 'Granted scopes'
                                            ],
                                            'acquired_at' => [
                                                'type' => 'string',
                                                'format' => 'date-time',
                                                'description' => 'ISO 8601 timestamp when token was acquired'
                                            ],
                                        ],
                                        'required' => ['access_token', 'token_type']
                                    ]
                                ]
                            ]
                        ],
                        '400' => [
                            'description' => 'Bad Request - Client Credentials not enabled'
                        ],
                        '401' => [
                            'description' => 'Unauthorized - Authentication failed'
                        ],
                        '500' => [
                            'description' => 'Internal Server Error'
                        ]
                    ]
                ],
                'post' => [
                    'summary' => 'Refresh access token',
                    'description' => 'Gets a new access token (Client Credentials flow does not have refresh tokens)',
                    'operationId' => 'refreshClientCredentialsToken',
                    'tags' => ['oauth'],
                    'responses' => [
                        '200' => [
                            'description' => 'Successful token response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'access_token' => [
                                                'type' => 'string',
                                                'description' => 'The access token to use for API calls'
                                            ],
                                            'token_type' => [
                                                'type' => 'string',
                                                'description' => 'Type of token (usually Bearer)'
                                            ],
                                            'expires_in' => [
                                                'type' => 'integer',
                                                'description' => 'Token expiration time in seconds'
                                            ],
                                            'scope' => [
                                                'type' => 'string',
                                                'description' => 'Granted scopes'
                                            ],
                                            'acquired_at' => [
                                                'type' => 'string',
                                                'format' => 'date-time',
                                                'description' => 'ISO 8601 timestamp when token was acquired'
                                            ],
                                        ],
                                        'required' => ['access_token', 'token_type']
                                    ]
                                ]
                            ]
                        ],
                        '400' => [
                            'description' => 'Bad Request'
                        ],
                        '401' => [
                            'description' => 'Unauthorized'
                        ]
                    ]
                ],
                'delete' => [
                    'summary' => 'Clear cached token',
                    'description' => 'Removes the cached access token from memory',
                    'operationId' => 'clearCachedToken',
                    'tags' => ['oauth'],
                    'responses' => [
                        '200' => [
                            'description' => 'Token cache cleared',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => [
                                                'type' => 'boolean',
                                                'description' => 'Operation success status'
                                            ],
                                            'message' => [
                                                'type' => 'string',
                                                'description' => 'Response message'
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        '400' => [
                            'description' => 'Bad Request'
                        ]
                    ]
                ]
            ]
        ];

        return $base;
    }
}