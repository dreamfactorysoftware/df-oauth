# Azure AD OAuth Client Credentials Flow Documentation

## Table of Contents
- [Overview](#overview)
- [Architecture](#architecture)
- [Configuration](#configuration)
- [API Endpoints](#api-endpoints)
- [Usage Examples](#usage-examples)
- [Implementation Details](#implementation-details)
- [Testing Guide](#testing-guide)
- [Troubleshooting](#troubleshooting)
- [Security Considerations](#security-considerations)

## Overview

This documentation describes the implementation of OAuth 2.0 Client Credentials flow for Azure Active Directory (Azure AD/Entra ID) in DreamFactory. The Client Credentials flow enables service-to-service authentication without user interaction, allowing applications to authenticate directly using a client ID and client secret.

### Key Features
- **Service-to-Service Authentication**: No user interaction required
- **Machine-to-Machine (M2M) Communication**: Ideal for backend services, daemons, and automated processes
- **Azure AD/Entra ID Integration**: Full support for Microsoft's identity platform
- **Dual Flow Support**: Same service can support both Authorization Code and Client Credentials flows
- **Token Caching**: Automatic token caching with expiration management
- **API Documentation**: Auto-generated OpenAPI/Swagger documentation

### Use Cases
- Background services accessing Microsoft Graph API
- Automated data synchronization with Azure resources
- Server-to-server API calls
- CI/CD pipeline authentication
- Microservices authentication

## Architecture

### Component Structure

```
df-oauth/
├── src/
│   ├── Services/
│   │   └── AzureAD.php                 # Main service class
│   ├── Components/
│   │   ├── AzureADProvider.php         # Authorization Code flow provider
│   │   └── AzureADClientCredentialsProvider.php  # Client Credentials flow provider
│   ├── Resources/
│   │   └── ClientCredentials.php       # API endpoint resource
│   ├── Models/
│   │   └── OAuthConfig.php            # Configuration model (updated)
│   └── ServiceProvider.php            # Service registration
└── database/
    └── migrations/
        └── 2025_01_29_100000_add_azure_ad_client_credentials_fields.php
```

### Flow Diagram

```mermaid
sequenceDiagram
    participant App as DreamFactory App
    participant Service as Azure AD Service
    participant Provider as Client Credentials Provider
    participant Azure as Azure AD Token Endpoint
    participant Cache as Token Cache

    App->>Service: GET /api/v2/{service}/client_credentials
    Service->>Cache: Check for cached token
    alt Token in cache
        Cache-->>Service: Return cached token
    else No cached token
        Service->>Provider: Request new token
        Provider->>Azure: POST /oauth2/v2.0/token
        Note over Azure: Validate client_id & client_secret
        Azure-->>Provider: Return access token
        Provider-->>Service: Return token data
        Service->>Cache: Store token with TTL
    end
    Service-->>App: Return access token response
```

## Configuration

### Database Schema

The implementation adds the following fields to the `oauth_config` table:

| Field | Type | Description |
|-------|------|-------------|
| `tenant_id` | string | Azure AD Tenant ID or domain (e.g., "your-tenant.onmicrosoft.com") |
| `authority_url` | string | OAuth authority URL (optional, defaults to Microsoft's login URL) |
| `scopes` | text | Space-separated list of scopes for the token request |
| `grant_type` | string | OAuth grant type: "authorization_code" or "client_credentials" |
| `is_client_credentials` | boolean | Flag to enable Client Credentials flow |

### Service Configuration

#### Via Admin UI

1. Navigate to **Services** → **Create**
2. Select **Azure AD OAuth** from the OAuth service types
3. Configure the following fields:

   **Basic Configuration:**
   - **Name**: Unique service identifier (e.g., `azure_ad_api`)
   - **Label**: Display name (e.g., "Azure AD API Access")
   - **Description**: Service description

   **OAuth Configuration:**
   - **Client ID**: Azure AD Application (client) ID
   - **Client Secret**: Application secret from Azure AD
   - **Tenant ID**: Your Azure AD tenant ID or domain
   - **Grant Type**: Select "Client Credentials"
   - **Enable Client Credentials Flow**: Check this box
   - **Scopes**: Enter required scopes (e.g., `https://graph.microsoft.com/.default`)
   - **Authority URL**: (Optional) Custom authority URL

#### Via API

```json
POST /api/v2/system/service

{
  "name": "azure_ad_api",
  "label": "Azure AD API Access",
  "description": "Service-to-service authentication for Microsoft Graph API",
  "type": "oauth_azure_ad",
  "is_active": true,
  "config": {
    "client_id": "your-client-id",
    "client_secret": "your-client-secret",
    "tenant_id": "your-tenant-id",
    "grant_type": "client_credentials",
    "is_client_credentials": true,
    "scopes": "https://graph.microsoft.com/.default",
    "authority_url": "https://login.microsoftonline.com/{tenant_id}"
  }
}
```

#### Via Environment Variables

```bash
# .env file
AZURE_AD_CLIENT_ID=your-client-id
AZURE_AD_CLIENT_SECRET=your-client-secret
AZURE_AD_TENANT_ID=your-tenant-id
AZURE_AD_SCOPES="https://graph.microsoft.com/.default"
```

### Azure AD Application Setup

1. **Register Application in Azure Portal:**
   - Go to Azure Portal → Azure Active Directory → App registrations
   - Click "New registration"
   - Enter application name
   - Select "Accounts in this organizational directory only"
   - No redirect URI needed for Client Credentials flow

2. **Configure Application:**
   - Note the **Application (client) ID**
   - Note the **Directory (tenant) ID**
   - Go to "Certificates & secrets"
   - Create a new client secret and save the value

3. **Configure API Permissions:**
   - Go to "API permissions"
   - Click "Add a permission"
   - Choose the API you want to access (e.g., Microsoft Graph)
   - Select "Application permissions" (not Delegated)
   - Choose required permissions
   - **Important**: Admin consent is required for application permissions

## API Endpoints

### Get Access Token

**Endpoint:** `GET /api/v2/{service_name}/client_credentials`

**Description:** Obtains a new access token using Client Credentials flow

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsIng1dCI6...",
  "token_type": "Bearer",
  "expires_in": 3599,
  "scope": "https://graph.microsoft.com/.default",
  "acquired_at": "2025-01-29T12:00:00Z"
}
```

### Refresh Token

**Endpoint:** `POST /api/v2/{service_name}/client_credentials`

**Description:** Gets a new access token (Client Credentials doesn't support refresh tokens, so this simply requests a new token)

**Response:** Same as GET endpoint

### Clear Cached Token

**Endpoint:** `DELETE /api/v2/{service_name}/client_credentials`

**Description:** Removes the cached access token

**Response:**
```json
{
  "success": true,
  "message": "Token cache cleared successfully."
}
```

## Usage Examples

### Using cURL

```bash
# Get access token
curl -X GET \
  http://your-dreamfactory-instance/api/v2/azure_ad_api/client_credentials \
  -H 'X-DreamFactory-Api-Key: your-api-key' \
  -H 'X-DreamFactory-Session-Token: your-session-token'

# Response
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3599,
  "scope": "https://graph.microsoft.com/.default",
  "acquired_at": "2025-01-29T12:00:00Z"
}
```

### Using the Token with Microsoft Graph API

```bash
# Use the access token to call Microsoft Graph
curl -X GET \
  https://graph.microsoft.com/v1.0/users \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### JavaScript Example

```javascript
// Get access token from DreamFactory
async function getAzureADToken() {
  const response = await fetch('http://your-df-instance/api/v2/azure_ad_api/client_credentials', {
    headers: {
      'X-DreamFactory-Api-Key': 'your-api-key',
      'X-DreamFactory-Session-Token': 'your-session-token'
    }
  });

  const data = await response.json();
  return data.access_token;
}

// Use token to call Microsoft Graph
async function getUsers() {
  const token = await getAzureADToken();

  const response = await fetch('https://graph.microsoft.com/v1.0/users', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });

  return response.json();
}
```

### Python Example

```python
import requests

# DreamFactory configuration
DF_URL = "http://your-df-instance"
DF_API_KEY = "your-api-key"
DF_SESSION_TOKEN = "your-session-token"
SERVICE_NAME = "azure_ad_api"

def get_azure_ad_token():
    """Get access token from DreamFactory Azure AD service"""
    headers = {
        'X-DreamFactory-Api-Key': DF_API_KEY,
        'X-DreamFactory-Session-Token': DF_SESSION_TOKEN
    }

    response = requests.get(
        f"{DF_URL}/api/v2/{SERVICE_NAME}/client_credentials",
        headers=headers
    )

    if response.status_code == 200:
        return response.json()['access_token']
    else:
        raise Exception(f"Failed to get token: {response.text}")

def call_graph_api():
    """Example: Call Microsoft Graph API using the token"""
    token = get_azure_ad_token()

    headers = {
        'Authorization': f'Bearer {token}'
    }

    response = requests.get(
        'https://graph.microsoft.com/v1.0/users',
        headers=headers
    )

    return response.json()

# Usage
users = call_graph_api()
print(f"Found {len(users['value'])} users")
```

### PHP Example

```php
<?php

class AzureADClient {
    private $dfUrl;
    private $apiKey;
    private $sessionToken;
    private $serviceName;

    public function __construct($dfUrl, $apiKey, $sessionToken, $serviceName) {
        $this->dfUrl = $dfUrl;
        $this->apiKey = $apiKey;
        $this->sessionToken = $sessionToken;
        $this->serviceName = $serviceName;
    }

    public function getAccessToken() {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => "{$this->dfUrl}/api/v2/{$this->serviceName}/client_credentials",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "X-DreamFactory-Api-Key: {$this->apiKey}",
                "X-DreamFactory-Session-Token: {$this->sessionToken}"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['access_token'];
        }

        throw new Exception("Failed to get access token: $response");
    }

    public function callGraphAPI($endpoint) {
        $token = $this->getAccessToken();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://graph.microsoft.com/v1.0/$endpoint",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token"
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

// Usage
$client = new AzureADClient(
    'http://your-df-instance',
    'your-api-key',
    'your-session-token',
    'azure_ad_api'
);

$users = $client->callGraphAPI('users');
echo "Found " . count($users['value']) . " users\n";
```

## Implementation Details

### Token Caching

The implementation includes automatic token caching to minimize requests to Azure AD:

- Tokens are cached using Laravel's cache system
- Cache key format: `azure_ad_cc_token_{service_id}`
- Cache TTL is set to token expiration time minus 60 seconds (safety buffer)
- Cache is automatically cleared on service configuration changes

### Error Handling

The implementation includes comprehensive error handling:

1. **Configuration Errors**: Validated during service creation/update
2. **Authentication Errors**: Detailed error messages from Azure AD
3. **Network Errors**: Timeout and connection error handling
4. **Token Errors**: Invalid token format detection

### Logging

All token requests and errors are logged for debugging:

```php
// Success logging
Log::info('Azure AD Client Credentials token obtained successfully', [
    'tenant_id' => $this->tenantId,
    'scopes' => $this->scopes,
    'expires_in' => $data['expires_in']
]);

// Error logging
Log::error('Azure AD token request failed', [
    'error' => $e->getMessage(),
    'tenant_id' => $this->tenantId,
    'endpoint' => $this->tokenEndpoint
]);
```

## Testing Guide

### Manual Testing

1. **Test Token Acquisition:**
```bash
# Should return access token
curl -X GET http://localhost/api/v2/azure_ad_api/client_credentials \
  -H 'X-DreamFactory-Api-Key: your-key'
```

2. **Test Token Caching:**
```bash
# First request - hits Azure AD
time curl -X GET http://localhost/api/v2/azure_ad_api/client_credentials

# Second request - uses cache (should be faster)
time curl -X GET http://localhost/api/v2/azure_ad_api/client_credentials
```

3. **Test Cache Clear:**
```bash
# Clear cache
curl -X DELETE http://localhost/api/v2/azure_ad_api/client_credentials

# Next GET should hit Azure AD again
curl -X GET http://localhost/api/v2/azure_ad_api/client_credentials
```

### Unit Testing

Create a test file at `tests/AzureADClientCredentialsTest.php`:

```php
<?php

namespace DreamFactory\Core\OAuth\Tests;

use DreamFactory\Core\OAuth\Services\AzureAD;
use DreamFactory\Core\OAuth\Components\AzureADClientCredentialsProvider;
use Tests\TestCase;
use Mockery;

class AzureADClientCredentialsTest extends TestCase
{
    public function testGetClientCredentialsToken()
    {
        // Mock the provider
        $mockProvider = Mockery::mock(AzureADClientCredentialsProvider::class);
        $mockProvider->shouldReceive('getAccessToken')
            ->once()
            ->andReturn([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'test-scope'
            ]);

        // Create service with mocked provider
        $service = new AzureAD([
            'config' => [
                'client_id' => 'test-client',
                'client_secret' => 'test-secret',
                'tenant_id' => 'test-tenant',
                'is_client_credentials' => true,
                'grant_type' => 'client_credentials'
            ]
        ]);

        // Inject mock provider
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('provider');
        $property->setAccessible(true);
        $property->setValue($service, $mockProvider);

        // Test token acquisition
        $result = $service->getClientCredentialsToken();

        $this->assertArrayHasKey('access_token', $result);
        $this->assertEquals('test-token', $result['access_token']);
        $this->assertEquals('Bearer', $result['token_type']);
    }

    public function testClientCredentialsProviderValidation()
    {
        $provider = new AzureADClientCredentialsProvider(
            'test-client',
            'test-secret',
            'test-tenant'
        );

        $this->assertEquals('test-tenant', $provider->getTenantId());
        $this->assertEquals('https://graph.microsoft.com/.default', $provider->getScopes());
        $this->assertStringContainsString('test-tenant', $provider->getAuthorityUrl());
    }
}
```

### Integration Testing

```bash
# Run migrations
docker compose exec web php artisan migrate

# Create test service via API
curl -X POST http://localhost/api/v2/system/service \
  -H 'Content-Type: application/json' \
  -H 'X-DreamFactory-Api-Key: your-key' \
  -d '{
    "name": "azure_test",
    "type": "oauth_azure_ad",
    "config": {
      "client_id": "test-client-id",
      "client_secret": "test-secret",
      "tenant_id": "test-tenant",
      "is_client_credentials": true,
      "grant_type": "client_credentials",
      "scopes": "https://graph.microsoft.com/.default"
    }
  }'

# Test the endpoint
curl -X GET http://localhost/api/v2/azure_test/client_credentials
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Invalid Client Error

**Error:**
```json
{
  "error": "invalid_client",
  "error_description": "AADSTS7000215: Invalid client secret provided"
}
```

**Solution:**
- Verify client ID and secret are correct
- Check secret hasn't expired in Azure Portal
- Ensure no extra spaces in configuration

#### 2. Invalid Scope Error

**Error:**
```json
{
  "error": "invalid_scope",
  "error_description": "AADSTS70011: The provided value for the input parameter 'scope' is not valid"
}
```

**Solution:**
- Use `.default` scope for application permissions
- Format: `https://graph.microsoft.com/.default`
- For custom APIs: `api://{client-id}/.default`

#### 3. Unauthorized Client Error

**Error:**
```json
{
  "error": "unauthorized_client",
  "error_description": "AADSTS700016: Application not found in the directory"
}
```

**Solution:**
- Verify tenant ID is correct
- Ensure application is registered in the specified tenant
- Check application hasn't been deleted

#### 4. Consent Required Error

**Error:**
```json
{
  "error": "consent_required",
  "error_description": "AADSTS65001: The user or administrator has not consented to use the application"
}
```

**Solution:**
- Admin consent is required for application permissions
- Go to Azure Portal → App registrations → API permissions
- Click "Grant admin consent for [Tenant]"

#### 5. Token Cache Issues

**Symptom:** Tokens not being cached or cache cleared unexpectedly

**Solution:**
```bash
# Check cache driver in .env
CACHE_DRIVER=redis  # or file, database, etc.

# Clear all cache
docker compose exec web php artisan cache:clear

# Check Redis connection (if using Redis)
docker compose exec web redis-cli ping
```

### Debug Mode

Enable debug logging for detailed troubleshooting:

```php
// In config/logging.php
'channels' => [
    'oauth' => [
        'driver' => 'single',
        'path' => storage_path('logs/oauth.log'),
        'level' => 'debug',
    ],
],

// In your code
Log::channel('oauth')->debug('Token request', [
    'endpoint' => $this->tokenEndpoint,
    'params' => $params
]);
```

## Security Considerations

### Best Practices

1. **Secret Management:**
   - Never commit client secrets to version control
   - Use environment variables or secure vaults
   - Rotate secrets regularly
   - Use certificate-based authentication for production

2. **Scope Limitation:**
   - Request only required permissions
   - Use least-privilege principle
   - Review permissions regularly
   - Remove unused permissions

3. **Token Security:**
   - Tokens are stored encrypted in cache
   - Set appropriate cache TTL
   - Clear cache on logout/service deletion
   - Monitor token usage

4. **Network Security:**
   - Always use HTTPS for API calls
   - Verify SSL certificates
   - Implement request rate limiting
   - Use IP allowlisting where possible

5. **Audit and Monitoring:**
   - Log all token requests
   - Monitor for unusual patterns
   - Set up alerts for authentication failures
   - Regular security audits

### Security Checklist

- [ ] Client secrets stored securely (environment variables/vault)
- [ ] Minimum required permissions configured
- [ ] Admin consent granted for application permissions
- [ ] SSL/TLS verification enabled
- [ ] Token caching configured with appropriate TTL
- [ ] Logging and monitoring in place
- [ ] Regular secret rotation schedule
- [ ] IP restrictions configured (if applicable)
- [ ] Rate limiting implemented
- [ ] Security headers configured

## Migration from Other Authentication Methods

### From API Keys to Client Credentials

```php
// Before: Using API key
$headers = ['Authorization' => 'ApiKey ' . $apiKey];

// After: Using Client Credentials
$token = $this->getClientCredentialsToken();
$headers = ['Authorization' => 'Bearer ' . $token['access_token']];
```

### From User Authentication to Service Authentication

```php
// Before: User login required
$user->login($username, $password);
$token = $user->getAccessToken();

// After: Direct service authentication
$service = new AzureAD($config);
$token = $service->getClientCredentialsToken();
```

## Appendix

### Supported Scopes for Common Services

| Service | Scope |
|---------|-------|
| Microsoft Graph | `https://graph.microsoft.com/.default` |
| Azure Key Vault | `https://vault.azure.net/.default` |
| Azure Storage | `https://storage.azure.com/.default` |
| Custom API | `api://{client-id}/.default` |

### Token Claims

Sample decoded JWT token claims:

```json
{
  "aud": "https://graph.microsoft.com",
  "iss": "https://sts.windows.net/{tenant-id}/",
  "iat": 1706532000,
  "nbf": 1706532000,
  "exp": 1706535600,
  "aio": "...",
  "app_displayname": "Your App Name",
  "appid": "your-client-id",
  "appidacr": "1",
  "idp": "https://sts.windows.net/{tenant-id}/",
  "oid": "object-id",
  "roles": ["User.Read.All", "Group.Read.All"],
  "sub": "subject-id",
  "tenant_region_scope": "NA",
  "tid": "tenant-id",
  "uti": "unique-token-id",
  "ver": "1.0"
}
```

### References

- [Microsoft Identity Platform - Client Credentials Flow](https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-client-creds-grant-flow)
- [Microsoft Graph API Permissions Reference](https://docs.microsoft.com/en-us/graph/permissions-reference)
- [Azure AD Application Registration](https://docs.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app)
- [DreamFactory OAuth Documentation](https://wiki.dreamfactory.com/DreamFactory/Features/OAuth)

---

**Last Updated:** January 29, 2025
**Version:** 1.0.0
**Author:** DreamFactory Development Team