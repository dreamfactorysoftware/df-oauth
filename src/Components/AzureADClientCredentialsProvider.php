<?php

namespace DreamFactory\Core\OAuth\Components;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use Illuminate\Support\Facades\Log;

/**
 * Azure AD Client Credentials OAuth Provider
 *
 * Implements OAuth 2.0 Client Credentials flow for Azure Active Directory.
 * This provider enables service-to-service authentication without user interaction.
 */
class AzureADClientCredentialsProvider
{
    /**
     * @var string Application (client) ID
     */
    protected $clientId;

    /**
     * @var string Client secret
     */
    protected $clientSecret;

    /**
     * @var string Azure AD tenant ID
     */
    protected $tenantId;

    /**
     * @var string Authority URL
     */
    protected $authorityUrl;

    /**
     * @var string Scopes for the access token
     */
    protected $scopes;

    /**
     * @var Client HTTP client for making requests
     */
    protected $httpClient;

    /**
     * @var string Token endpoint URL
     */
    protected $tokenEndpoint;

    /**
     * Constructor
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $tenantId
     * @param string|null $authorityUrl
     * @param string|null $scopes
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $tenantId,
        ?string $authorityUrl = null,
        ?string $scopes = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tenantId = $tenantId;

        // Set default authority URL if not provided
        if (empty($authorityUrl)) {
            $this->authorityUrl = 'https://login.microsoftonline.com/' . $tenantId;
        } else {
            $this->authorityUrl = str_replace('{tenant_id}', $tenantId, $authorityUrl);
        }

        // Set default scope if not provided (Microsoft Graph API default scope)
        $this->scopes = $scopes ?: 'https://graph.microsoft.com/.default';

        // Build token endpoint
        $this->tokenEndpoint = rtrim($this->authorityUrl, '/') . '/oauth2/v2.0/token';

        // Initialize HTTP client
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => true, // Verify SSL certificates
        ]);
    }

    /**
     * Get an access token using client credentials flow
     *
     * @return array
     * @throws UnauthorizedException
     * @throws InternalServerErrorException
     */
    public function getAccessToken(): array
    {
        try {
            $response = $this->httpClient->post($this->tokenEndpoint, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => $this->scopes,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Azure AD token request failed', [
                    'status_code' => $statusCode,
                    'response' => $body,
                    'tenant_id' => $this->tenantId,
                ]);
                throw new UnauthorizedException('Failed to obtain access token from Azure AD.');
            }

            $data = json_decode($body, true);

            if (!isset($data['access_token'])) {
                Log::error('Azure AD token response missing access_token', [
                    'response' => $data,
                    'tenant_id' => $this->tenantId,
                ]);
                throw new InternalServerErrorException('Invalid token response from Azure AD.');
            }

            Log::info('Azure AD Client Credentials token obtained successfully', [
                'tenant_id' => $this->tenantId,
                'scopes' => $this->scopes,
                'expires_in' => $data['expires_in'] ?? 'unknown',
            ]);

            return $data;

        } catch (GuzzleException $e) {
            Log::error('Azure AD token request failed with exception', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantId,
                'endpoint' => $this->tokenEndpoint,
            ]);

            // Parse error response if available
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $errorResponse = $e->getResponse();
                $errorBody = $errorResponse->getBody()->getContents();

                try {
                    $errorData = json_decode($errorBody, true);
                    if (isset($errorData['error_description'])) {
                        $errorMessage = $errorData['error_description'];
                    } elseif (isset($errorData['error'])) {
                        $errorMessage = $errorData['error'];
                    }
                } catch (\Exception $parseException) {
                    // If we can't parse the error, use the original message
                }
            }

            throw new UnauthorizedException('Azure AD authentication failed: ' . $errorMessage);
        }
    }

    /**
     * Validate token endpoint connectivity
     *
     * @return bool
     */
    public function validateEndpoint(): bool
    {
        try {
            // Try to reach the token endpoint with a HEAD request
            $response = $this->httpClient->request('GET', $this->tokenEndpoint, [
                'http_errors' => false,
                'timeout' => 5,
            ]);

            // We expect a 400 or 401 since we're not sending credentials
            // But this confirms the endpoint is reachable
            $statusCode = $response->getStatusCode();
            return in_array($statusCode, [400, 401, 405]);

        } catch (GuzzleException $e) {
            Log::warning('Azure AD endpoint validation failed', [
                'endpoint' => $this->tokenEndpoint,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the token endpoint URL
     *
     * @return string
     */
    public function getTokenEndpoint(): string
    {
        return $this->tokenEndpoint;
    }

    /**
     * Get the configured tenant ID
     *
     * @return string
     */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * Get the configured scopes
     *
     * @return string
     */
    public function getScopes(): string
    {
        return $this->scopes;
    }

    /**
     * Get the authority URL
     *
     * @return string
     */
    public function getAuthorityUrl(): string
    {
        return $this->authorityUrl;
    }
}