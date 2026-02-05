<?php
namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Components\AppRoleMapper;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\Role;
use DreamFactory\Core\Models\Service;

/**
 * Class OAuthConfig
 *
 * @package DreamFactory\Core\OAuth\Models
 */
class OAuthConfig extends BaseServiceConfigModel
{
    use AppRoleMapper;

    protected $table = 'oauth_config';

    protected $fillable = [
        'service_id',
        'default_role',
        'client_id',
        'client_secret',
        'redirect_url',
        'tenant_id',
        'authority_url',
        'scopes',
        'grant_type',
        'is_client_credentials',
        'icon_class',
        'custom_provider',
        'allow_new_users',
    ];

    protected $encrypted = ['client_secret'];

    protected $protected = ['client_secret'];

    /**
     * Hide provider-specific fields from the config schema.
     * These fields were added to this shared oauth_config table for specific providers,
     * but should not appear in the UI for other OAuth providers (Facebook, GitHub, etc.).
     * Provider-specific config classes (GoogleOAuthConfig, etc.) will unhide their relevant fields.
     */
    protected $hidden = [
        'tenant_id',
        'authority_url',
        'scopes',
        'grant_type',
        'is_client_credentials',
        'map_group_to_role',  // Google-specific, shown in GoogleOAuthConfig
    ];

    protected $casts = [
        'service_id'            => 'integer',
        'default_role'          => 'integer',
        'custom_provider'       => 'boolean',
        'is_client_credentials' => 'boolean',
        'allow_new_users'       => 'boolean',
    ];

    protected $rules = [
        'client_id'     => 'required',
        'client_secret' => 'required',
        'redirect_url'  => 'required_unless:is_client_credentials,true|required_if:grant_type,authorization_code',
        'tenant_id'     => 'required_if:is_client_credentials,true',
    ];

    /**
     * Handle model saving with proper redirect_url handling for Client Credentials flow
     *
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        // If Client Credentials flow is enabled, ensure redirect_url is null
        if ($this->is_client_credentials === true || $this->grant_type === 'client_credentials') {
            $this->redirect_url = null;
        }

        return parent::save($options);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'default_role':
                $roles = Role::whereIsActive(1)->get();
                $roleList = [];
                foreach ($roles as $role) {
                    $roleList[] = [
                        'label' => $role->name,
                        'name'  => $role->id
                    ];
                }

                $schema['type'] = 'picklist';
                $schema['values'] = $roleList;
                $schema['description'] = 'Select a default role for users logging in with this OAuth service type.';
                break;
            case 'client_id':
                $schema['label'] = 'Client ID';
                $schema['description'] =
                    'A public string used by the service to identify your app and to build authorization URLs.';
                break;
            case 'client_secret':
                $schema['description'] =
                    'A private string used by the service to authenticate the identity of the application.';
                break;
            case 'redirect_url':
                $schema['label'] = 'Redirect URL';
                $schema['description'] = 'The location the user will be redirected to after a successful login.';
                break;
            case 'icon_class':
                $schema['description'] = 'The icon to display for this OAuth service.';
                break;
            case 'custom_provider':
                $schema['label'] = 'Use custom OAuth 2.0 provider for this type';
                $schema['description'] =
                    'Some OAuth 2.0 type allows for custom/alternative provider in DreamFactory. ' .
                    'Check this if your OAuth type supports alternate provider and you want to use that.';
                break;
            case 'tenant_id':
                $schema['label'] = 'Tenant ID';
                $schema['description'] =
                    'Azure AD Tenant ID or domain (e.g., "your-tenant.onmicrosoft.com" or GUID). Required for Azure AD Client Credentials.';
                break;
            case 'authority_url':
                $schema['label'] = 'Authority URL';
                $schema['description'] =
                    'OAuth authority URL. For Azure AD, use "https://login.microsoftonline.com/{tenant_id}". Leave blank to use default.';
                break;
            case 'scopes':
                $schema['label'] = 'Scopes';
                $schema['description'] =
                    'Space-separated list of scopes for client credentials. For Microsoft Graph API, use "https://graph.microsoft.com/.default"';
                break;
            case 'grant_type':
                $schema['label'] = 'Grant Type';
                $schema['type'] = 'picklist';
                $schema['values'] = [
                    ['label' => 'Authorization Code', 'name' => 'authorization_code'],
                    ['label' => 'Client Credentials', 'name' => 'client_credentials'],
                ];
                $schema['default'] = 'authorization_code';
                $schema['description'] =
                    'OAuth grant type. Use "Client Credentials" for service-to-service authentication without user interaction.';
                break;
            case 'is_client_credentials':
                $schema['label'] = 'Enable Client Credentials Flow';
                $schema['description'] =
                    'Enable OAuth 2.0 Client Credentials flow for service-to-service authentication (Azure AD/Entra).';
                break;
            case 'allow_new_users':
                $schema['label'] = 'Allow New User Creation';
                $schema['type'] = 'boolean';
                $schema['default'] = true;
                $schema['description'] =
                    'Allow automatic creation of new users during OAuth login. If disabled, only existing users can login through this OAuth service.';
                break;
        }
    }
}