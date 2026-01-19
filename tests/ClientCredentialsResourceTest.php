<?php

use DreamFactory\Core\OAuth\Resources\ClientCredentials;
use DreamFactory\Core\OAuth\Services\AzureAD;
use DreamFactory\Core\Exceptions\BadRequestException;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\Request;

/**
 * Test suite for ClientCredentials OAuth Resource
 */
class ClientCredentialsResourceTest extends TestCase
{
    private $mockService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock Azure AD service
        $this->mockService = $this->createMock(AzureAD::class);
        $this->mockService->method('getName')->willReturn('test-azure-ad');
    }

    /**
     * Test GET request for obtaining access token
     */
    public function testHandleGET()
    {
        $expectedResult = [
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'https://graph.microsoft.com/.default',
            'acquired_at' => '2025-01-29T10:00:00+00:00'
        ];

        $this->mockService
            ->method('getClientCredentialsToken')
            ->willReturn($expectedResult);

        $resource = new ClientCredentials([]);

        // Use reflection to inject the mock service
        $reflection = new ReflectionClass($resource);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($resource, $this->mockService);

        $handleGETMethod = $reflection->getMethod('handleGET');
        $handleGETMethod->setAccessible(true);

        $result = $handleGETMethod->invoke($resource);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test POST request for refreshing access token
     */
    public function testHandlePOST()
    {
        $expectedResult = [
            'access_token' => 'refreshed-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'https://graph.microsoft.com/.default',
            'acquired_at' => '2025-01-29T10:00:00+00:00'
        ];

        $this->mockService
            ->method('refreshClientCredentialsToken')
            ->willReturn($expectedResult);

        $resource = new ClientCredentials([]);

        // Use reflection to inject the mock service
        $reflection = new ReflectionClass($resource);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($resource, $this->mockService);

        $handlePOSTMethod = $reflection->getMethod('handlePOST');
        $handlePOSTMethod->setAccessible(true);

        $result = $handlePOSTMethod->invoke($resource);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test DELETE request for clearing cached token
     */
    public function testHandleDELETE()
    {
        $expectedResult = [
            'success' => true,
            'message' => 'Token cache cleared successfully.'
        ];

        $this->mockService
            ->method('clearToken')
            ->willReturn($expectedResult);

        $resource = new ClientCredentials([]);

        // Use reflection to inject the mock service
        $reflection = new ReflectionClass($resource);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($resource, $this->mockService);

        $handleDELETEMethod = $reflection->getMethod('handleDELETE');
        $handleDELETEMethod->setAccessible(true);

        $result = $handleDELETEMethod->invoke($resource);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test GET request fails with non-Azure AD service
     */
    public function testHandleGETWithNonAzureADService()
    {
        $nonAzureADService = $this->createMock(\DreamFactory\Core\OAuth\Services\Google::class);

        $resource = new ClientCredentials([]);

        // Use reflection to inject the non-Azure AD service
        $reflection = new ReflectionClass($resource);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($resource, $nonAzureADService);

        $handleGETMethod = $reflection->getMethod('handleGET');
        $handleGETMethod->setAccessible(true);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Client Credentials flow is only supported for Azure AD services.');

        $handleGETMethod->invoke($resource);
    }

    /**
     * Test POST request fails with non-Azure AD service
     */
    public function testHandlePOSTWithNonAzureADService()
    {
        $nonAzureADService = $this->createMock(\DreamFactory\Core\OAuth\Services\Google::class);

        $resource = new ClientCredentials([]);

        // Use reflection to inject the non-Azure AD service
        $reflection = new ReflectionClass($resource);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($resource, $nonAzureADService);

        $handlePOSTMethod = $reflection->getMethod('handlePOST');
        $handlePOSTMethod->setAccessible(true);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Client Credentials flow is only supported for Azure AD services.');

        $handlePOSTMethod->invoke($resource);
    }

    /**
     * Test DELETE request fails with non-Azure AD service
     */
    public function testHandleDELETEWithNonAzureADService()
    {
        $nonAzureADService = $this->createMock(\DreamFactory\Core\OAuth\Services\Google::class);

        $resource = new ClientCredentials([]);

        // Use reflection to inject the non-Azure AD service
        $reflection = new ReflectionClass($resource);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($resource, $nonAzureADService);

        $handleDELETEMethod = $reflection->getMethod('handleDELETE');
        $handleDELETEMethod->setAccessible(true);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Client Credentials flow is only supported for Azure AD services.');

        $handleDELETEMethod->invoke($resource);
    }

    /**
     * Test resource constants
     */
    public function testResourceConstants()
    {
        $this->assertEquals('client_credentials', ClientCredentials::RESOURCE_NAME);
    }

    /**
     * Test resource name property
     */
    public function testResourceNameProperty()
    {
        $resource = new ClientCredentials([]);

        $reflection = new ReflectionClass($resource);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);

        $this->assertEquals('client_credentials', $nameProperty->getValue($resource));
    }

    /**
     * Test API documentation structure
     */
    public function testGetApiDocInfo()
    {
        $resource = new ClientCredentials([]);
        $apiDoc = $resource->getApiDocInfo();

        $this->assertArrayHasKey('paths', $apiDoc);
        $this->assertArrayHasKey('/client_credentials', $apiDoc['paths']);

        $paths = $apiDoc['paths']['/client_credentials'];

        // Test GET endpoint documentation
        $this->assertArrayHasKey('get', $paths);
        $this->assertEquals('Get access token using Client Credentials', $paths['get']['summary']);
        $this->assertEquals('getClientCredentialsToken', $paths['get']['operationId']);
        $this->assertArrayHasKey('responses', $paths['get']);

        // Test POST endpoint documentation
        $this->assertArrayHasKey('post', $paths);
        $this->assertEquals('Refresh access token', $paths['post']['summary']);
        $this->assertEquals('refreshClientCredentialsToken', $paths['post']['operationId']);

        // Test DELETE endpoint documentation
        $this->assertArrayHasKey('delete', $paths);
        $this->assertEquals('Clear cached token', $paths['delete']['summary']);
        $this->assertEquals('clearCachedToken', $paths['delete']['operationId']);
    }
}