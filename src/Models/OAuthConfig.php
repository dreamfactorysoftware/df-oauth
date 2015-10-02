<?php
namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Contracts\ServiceConfigHandlerInterface;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\Role;
use DreamFactory\Core\Models\Service;

/**
 * Class OAuthConfig
 *
 * @package DreamFactory\Core\OAuth\Models
 */
class OAuthConfig extends BaseServiceConfigModel implements ServiceConfigHandlerInterface
{
    protected $table = 'oauth_config';

    protected $fillable = ['service_id', 'default_role', 'client_id', 'client_secret', 'redirect_url', 'icon_class'];

    protected $encrypted = ['client_secret'];

    protected $casts = ['service_id' => 'integer', 'default_role' => 'integer'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * @param array     $config
     * @param bool|true $create
     *
     * @return bool
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    public static function validateConfig($config, $create = true)
    {
        $validator = static::makeValidator($config, [
            'default_role'  => 'required',
            'client_id'     => 'required',
            'client_secret' => 'required',
            'redirect_url'  => 'required'
        ], $create);

        if ($validator->fails()) {
            $messages = $validator->messages()->getMessages();
            throw new BadRequestException('Validation failed.', null, null, $messages);
        }

        return true;
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        $roles = Role::whereIsActive(1)->get();
        $roleList = [];

        foreach($roles as $role){
            $roleList[] = [
                'label' => $role->name,
                'name'  => $role->id
            ];
        }

        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'default_role':
                $schema['type'] = 'picklist';
                $schema['values'] = $roleList;
                $schema['description'] = 'Select a default role for users logging in with this OAuth service type.';
                break;
            case 'client_id':
                $schema['label'] = 'Client ID';
                $schema['description'] = 'A public string used by the service to identify your app and to build authorization URLs.';
                break;
            case 'client_secret':
                $schema['description'] = 'A private string used by the service to authenticate the identity of the application.';
                break;
            case 'redirect_url':
                $schema['label'] = 'Redirect URL';
                $schema['description'] = 'The location the user will be redirected to after a successful login.';
                break;
            case 'icon_class':
                $schema['description'] = 'The icon to display for this OAuth service.';
                break;
        }
    }
}