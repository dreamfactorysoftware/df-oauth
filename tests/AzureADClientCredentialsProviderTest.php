<?php

use DreamFactory\Core\OAuth\Components\AzureADClientCredentialsProvider;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for AzureADClientCredentialsProvider
 */
class AzureADClientCredentialsProviderTest extends TestCase
{
    private $clientId = 'test-client-id';
    private $clientSecret = 'test-client-secret';
    private $tenantId = 'test-tenant-id';
    private $scopes = 'https://graph.microsoft.com/.default';
    private $authorityUrl = 'https://login.microsoftonline.com/test-tenant-id';

    /**
     * Test successful provider instantiation with default values
     */
    public function testProviderInstantiation()
    {
        $provider = new AzureADClientCredentialsProvider(
            $this->clientId,
            $this->clientSecret,
            $this->tenantId
        );

        $this->assertEquals('https://login.microsoftonline.com/test-tenant-id', $provider->getAuthorityUrl());
        $this->assertEquals('https://graph.microsoft.com/.default', $provider->getScopes());
        $this->assertEquals('https://login.microsoftonline.com/test-tenant-id/oauth2/v2.0/token', $provider->getTokenEndpoint());
        $this->assertEquals('test-tenant-id', $provider->getTenantId());
    }

    /**
     * Test provider instantiation with custom authority URL
     */
    public function testProviderInstantiationWithCustomAuthority()
    {
        $customAuthority = 'https://login.microsoftonline.com/{tenant_id}';
        $provider = new AzureADClientCredentialsProvider(
            $this->clientId,
            $this->clientSecret,
            $this->tenantId,
            $customAuthority
        );

        $this->assertEquals('https://login.microsoftonline.com/test-tenant-id', $provider->getAuthorityUrl());
    }

    /**
     * Test provider instantiation with custom scopes
     */
    public function testProviderInstantiationWithCustomScopes()
    {
        $customScopes = 'https://api.contoso.com/.default';
        $provider = new AzureADClientCredentialsProvider(
            $this->clientId,
            $this->clientSecret,
            $this->tenantId,
            null,
            $customScopes
        );

        $this->assertEquals($customScopes, $provider->getScopes());
    }

    /**
     * Test successful token acquisition
     */
    public function testSuccessfulTokenAcquisition()
    {
        $mockResponse = [
            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'https://graph.microsoft.com/.default'
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);
        $result = $provider->getAccessToken();

        $this->assertEquals($mockResponse['access_token'], $result['access_token']);
        $this->assertEquals($mockResponse['token_type'], $result['token_type']);
        $this->assertEquals($mockResponse['expires_in'], $result['expires_in']);
        $this->assertEquals($mockResponse['scope'], $result['scope']);
    }

    /**
     * Test token acquisition with missing access token in response
     */
    public function testTokenAcquisitionMissingAccessToken()
    {
        $mockResponse = [
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($mockResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);

        $this->expectException(InternalServerErrorException::class);
        $this->expectExceptionMessage('Invalid token response from Azure AD.');

        $provider->getAccessToken();
    }

    /**
     * Test token acquisition with HTTP error response
     */
    public function testTokenAcquisitionHttpError()
    {
        $errorResponse = [
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed.'
        ];

        $mock = new MockHandler([
            new Response(401, ['Content-Type' => 'application/json'], json_encode($errorResponse))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Failed to obtain access token from Azure AD.');

        $provider->getAccessToken();
    }

    /**
     * Test token acquisition with network error
     */
    public function testTokenAcquisitionNetworkError()
    {
        $mock = new MockHandler([
            new RequestException('Connection timeout', new Request('POST', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Azure AD authentication failed: Connection timeout');

        $provider->getAccessToken();
    }

    /**
     * Test token acquisition with detailed error response
     */
    public function testTokenAcquisitionDetailedError()
    {
        $errorResponse = [
            'error' => 'invalid_scope',
            'error_description' => 'The provided scope is invalid'
        ];

        $mock = new MockHandler([
            new RequestException(
                'Bad Request',
                new Request('POST', 'test'),
                new Response(400, [], json_encode($errorResponse))
            )
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Azure AD authentication failed: The provided scope is invalid');

        $provider->getAccessToken();
    }

    /**
     * Test endpoint validation with reachable endpoint
     */
    public function testEndpointValidationSuccess()
    {
        $mock = new MockHandler([
            new Response(400) // Expected response for unauthenticated request
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);
        $result = $provider->validateEndpoint();

        $this->assertTrue($result);
    }

    /**
     * Test endpoint validation with unreachable endpoint
     */
    public function testEndpointValidationFailure()
    {
        $mock = new MockHandler([
            new RequestException('Connection refused', new Request('GET', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);
        $result = $provider->validateEndpoint();

        $this->assertFalse($result);
    }

    /**
     * Test endpoint validation with successful response (should still be valid)
     */
    public function testEndpointValidationWithUnauthorized()
    {
        $mock = new MockHandler([
            new Response(401) // Expected response for unauthenticated request
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);
        $result = $provider->validateEndpoint();

        $this->assertTrue($result);
    }

    /**
     * Test endpoint validation with method not allowed (should still be valid)
     */
    public function testEndpointValidationWithMethodNotAllowed()
    {
        $mock = new MockHandler([
            new Response(405) // Method not allowed - endpoint exists but GET not supported
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);
        $result = $provider->validateEndpoint();

        $this->assertTrue($result);
    }

    /**
     * Test endpoint validation with unexpected successful response
     */
    public function testEndpointValidationWithUnexpectedSuccess()
    {
        $mock = new MockHandler([
            new Response(200) // Unexpected successful response
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->createProviderWithMockClient($client);
        $result = $provider->validateEndpoint();

        $this->assertFalse($result);
    }

    /**
     * Helper method to create provider with mock HTTP client
     */
    private function createProviderWithMockClient(Client $client): AzureADClientCredentialsProvider
    {
        $provider = new AzureADClientCredentialsProvider(
            $this->clientId,
            $this->clientSecret,
            $this->tenantId,
            $this->authorityUrl,
            $this->scopes
        );

        // Use reflection to inject the mock client
        $reflection = new ReflectionClass($provider);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($provider, $client);

        return $provider;
    }
}