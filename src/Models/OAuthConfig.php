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
        'icon_class',
        'custom_provider',
    ];

    protected $encrypted = ['client_secret'];

    protected $protected = ['client_secret'];

    protected $casts = [
        'service_id'      => 'integer',
        'default_role'    => 'integer',
        'custom_provider' => 'boolean',
    ];

    protected $rules = [
        'client_id'     => 'required',
        'client_secret' => 'required',
        'redirect_url'  => 'required'
    ];

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
        }
    }
}