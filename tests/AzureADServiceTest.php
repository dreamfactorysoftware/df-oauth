<?php

use DreamFactory\Core\OAuth\Services\AzureAD;
use DreamFactory\Core\OAuth\Components\AzureADClientCredentialsProvider;
use DreamFactory\Core\OAuth\Components\AzureADProvider;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Test suite for AzureAD OAuth Service
 */
class AzureADServiceTest extends TestCase
{
    private $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig = [
            'name' => 'test-azure-ad',
            'id' => 999,
            'config' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'tenant_id' => 'test-tenant-id',
                'authority_url' => 'https://login.microsoftonline.com/test-tenant-id',
                'scopes' => 'https://graph.microsoft.com/.default',
            ]
        ];
    }

    /**
     * Test service instantiation with Client Credentials configuration
     */
    public function testServiceInstantiationClientCredentials()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;
        $config['config']['grant_type'] = 'client_credentials';

        $service = new AzureAD($config);

        $this->assertEquals('test-azure-ad', $service->getName());
        $this->assertEquals('azure_ad', $service->getProviderName());
        $this->assertInstanceOf(AzureADClientCredentialsProvider::class, $service->getProvider());
    }

    /**
     * Test service instantiation with Authorization Code configuration
     */
    public function testServiceInstantiationAuthorizationCode()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = false;
        $config['config']['grant_type'] = 'authorization_code';
        $config['config']['redirect_url'] = 'https://example.com/callback';

        $service = new AzureAD($config);

        $this->assertEquals('test-azure-ad', $service->getName());
        $this->assertEquals('azure_ad', $service->getProviderName());
        $this->assertInstanceOf(AzureADProvider::class, $service->getProvider());
    }

    /**
     * Test service instantiation fails with missing client ID
     */
    public function testServiceInstantiationMissingClientId()
    {
        $config = $this->mockConfig;
        unset($config['config']['client_id']);

        $this->expectException(InternalServerErrorException::class);
        $this->expectExceptionMessage('Azure AD OAuth service is not configured properly. Client ID and Client Secret are required.');

        new AzureAD($config);
    }

    /**
     * Test service instantiation fails with missing client secret
     */
    public function testServiceInstantiationMissingClientSecret()
    {
        $config = $this->mockConfig;
        unset($config['config']['client_secret']);

        $this->expectException(InternalServerErrorException::class);
        $this->expectExceptionMessage('Azure AD OAuth service is not configured properly. Client ID and Client Secret are required.');

        new AzureAD($config);
    }

    /**
     * Test service instantiation fails with missing redirect URL for authorization code flow
     */
    public function testServiceInstantiationMissingRedirectUrl()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = false;
        $config['config']['grant_type'] = 'authorization_code';
        // No redirect_url provided

        $this->expectException(InternalServerErrorException::class);
        $this->expectExceptionMessage('Azure AD OAuth service requires a redirect URL for Authorization Code flow.');

        new AzureAD($config);
    }

    /**
     * Test Client Credentials token acquisition
     */
    public function testGetClientCredentialsToken()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;

        $service = new AzureAD($config);

        // Mock the provider's getAccessToken method
        $mockProvider = $this->createMock(AzureADClientCredentialsProvider::class);
        $mockProvider->method('getAccessToken')->willReturn([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'https://graph.microsoft.com/.default'
        ]);

        // Use reflection to inject mock provider
        $reflection = new ReflectionClass($service);
        $providerProperty = $reflection->getProperty('provider');
        $providerProperty->setAccessible(true);
        $providerProperty->setValue($service, $mockProvider);

        $result = $service->getClientCredentialsToken();

        $this->assertEquals('test-access-token', $result['access_token']);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertEquals(3600, $result['expires_in']);
        $this->assertEquals('https://graph.microsoft.com/.default', $result['scope']);
        $this->assertArrayHasKey('acquired_at', $result);
    }

    /**
     * Test Client Credentials token acquisition fails when not enabled
     */
    public function testGetClientCredentialsTokenNotEnabled()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = false;

        $service = new AzureAD($config);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Client Credentials flow is not enabled for this service.');

        $service->getClientCredentialsToken();
    }

    /**
     * Test Client Credentials token acquisition fails with wrong provider type
     */
    public function testGetClientCredentialsTokenWrongProvider()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;

        $service = new AzureAD($config);

        // Mock the wrong provider type
        $mockProvider = $this->createMock(AzureADProvider::class);

        // Use reflection to inject mock provider
        $reflection = new ReflectionClass($service);
        $providerProperty = $reflection->getProperty('provider');
        $providerProperty->setAccessible(true);
        $providerProperty->setValue($service, $mockProvider);

        $this->expectException(InternalServerErrorException::class);
        $this->expectExceptionMessage('Provider is not configured for Client Credentials flow.');

        $service->getClientCredentialsToken();
    }

    /**
     * Test Client Credentials token refresh
     */
    public function testRefreshClientCredentialsToken()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;

        $service = new AzureAD($config);

        // Mock the provider's getAccessToken method
        $mockProvider = $this->createMock(AzureADClientCredentialsProvider::class);
        $mockProvider->method('getAccessToken')->willReturn([
            'access_token' => 'refreshed-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'https://graph.microsoft.com/.default'
        ]);

        // Use reflection to inject mock provider
        $reflection = new ReflectionClass($service);
        $providerProperty = $reflection->getProperty('provider');
        $providerProperty->setAccessible(true);
        $providerProperty->setValue($service, $mockProvider);

        $result = $service->refreshClientCredentialsToken();

        $this->assertEquals('refreshed-access-token', $result['access_token']);
    }

    /**
     * Test Client Credentials token refresh fails when not enabled
     */
    public function testRefreshClientCredentialsTokenNotEnabled()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = false;

        $service = new AzureAD($config);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('This method is only available for Client Credentials flow.');

        $service->refreshClientCredentialsToken();
    }

    /**
     * Test token validation with cached token
     */
    public function testValidateTokenWithCachedToken()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;

        $service = new AzureAD($config);

        // Mock cached token
        Cache::shouldReceive('get')
            ->once()
            ->with('azure_ad_cc_token_999')
            ->andReturn([
                'access_token' => 'cached-token',
                'token_type' => 'Bearer',
                'scope' => 'https://graph.microsoft.com/.default'
            ]);

        $result = $service->validateToken();

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['cached']);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertEquals('https://graph.microsoft.com/.default', $result['scope']);
    }

    /**
     * Test token validation without cached token
     */
    public function testValidateTokenWithoutCachedToken()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;

        $service = new AzureAD($config);

        // Mock no cached token
        Cache::shouldReceive('get')
            ->once()
            ->with('azure_ad_cc_token_999')
            ->andReturn(null);

        $result = $service->validateToken();

        $this->assertFalse($result['valid']);
        $this->assertFalse($result['cached']);
        $this->assertEquals('No valid token in cache. Please request a new token.', $result['message']);
    }

    /**
     * Test clear token cache
     */
    public function testClearToken()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;

        $service = new AzureAD($config);

        // Mock cache forget
        Cache::shouldReceive('forget')
            ->once()
            ->with('azure_ad_cc_token_999')
            ->andReturn(true);

        $result = $service->clearToken();

        $this->assertTrue($result['success']);
        $this->assertEquals('Token cache cleared successfully.', $result['message']);
    }

    /**
     * Test getApiDocInfo includes client credentials paths
     */
    public function testGetApiDocInfoClientCredentials()
    {
        $config = $this->mockConfig;
        $config['config']['is_client_credentials'] = true;

        $service = new AzureAD($config);

        $apiDoc = $service->getApiDocInfo();

        $this->assertArrayHasKey('/client_credentials', $apiDoc['paths']);
        $this->assertArrayHasKey('get', $apiDoc['paths']['/client_credentials']);
        $this->assertArrayHasKey('post', $apiDoc['paths']['/client_credentials']);
        $this->assertArrayHasKey('delete', $apiDoc['paths']['/client_credentials']);
    }

    /**
     * Test default configuration values
     */
    public function testDefaultConfigurationValues()
    {
        $config = [
            'name' => 'test-azure-ad',
            'id' => 999,
            'config' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                // Using minimal config to test defaults
            ]
        ];

        $service = new AzureAD($config);

        // Test that defaults are properly applied via reflection
        $reflection = new ReflectionClass($service);

        $tenantIdProperty = $reflection->getProperty('tenantId');
        $tenantIdProperty->setAccessible(true);
        $this->assertNull($tenantIdProperty->getValue($service));

        $grantTypeProperty = $reflection->getProperty('grantType');
        $grantTypeProperty->setAccessible(true);
        $this->assertEquals('authorization_code', $grantTypeProperty->getValue($service));

        $isClientCredentialsProperty = $reflection->getProperty('isClientCredentials');
        $isClientCredentialsProperty->setAccessible(true);
        $this->assertFalse($isClientCredentialsProperty->getValue($service));
    }

    /**
     * Test service constants
     */
    public function testServiceConstants()
    {
        $this->assertEquals('oauth_azure_ad', AzureAD::SERVICE_TYPE);
        $this->assertEquals('azure_ad', AzureAD::PROVIDER_NAME);
    }
}