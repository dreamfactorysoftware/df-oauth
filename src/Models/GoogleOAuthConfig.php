<?php

namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Models\Role;

class GoogleOAuthConfig extends OAuthConfig
{
    protected $fillable = [
        'service_id',
        'default_role',
        'client_id',
        'client_secret',
        'redirect_url',
        'icon_class',
        'allow_new_users',
        'map_group_to_role',
    ];

    /**
     * Override parent's hidden array to expose map_group_to_role for Google OAuth.
     * Other provider-specific fields remain hidden.
     */
    protected $hidden = [
        'tenant_id',
        'authority_url',
        'scopes',
        'grant_type',
        'is_client_credentials',
        // Note: map_group_to_role is NOT hidden for Google OAuth
    ];

    protected $casts = [
        'service_id'        => 'integer',
        'default_role'      => 'integer',
        'allow_new_users'   => 'boolean',
        'map_group_to_role' => 'boolean',
    ];

    protected $rules = [
        'client_id'     => 'required',
        'client_secret' => 'required',
        'redirect_url'  => 'required',
    ];

    /**
     * Get configuration including group-to-role mappings.
     *
     * @param int   $id
     * @param null  $local_config
     * @param bool  $protect
     * @return mixed
     */
    public static function getConfig($id, $local_config = null, $protect = true)
    {
        $config = parent::getConfig($id, $local_config, $protect);

        if ($config) {
            $groupRoleMaps = RoleGoogle::where('role_id', '>', 0)->get();
            $config['group_role_map'] = [];

            foreach ($groupRoleMaps as $map) {
                $config['group_role_map'][] = [
                    'role_id'     => $map->role_id,
                    'group_email' => $map->group_email,
                ];
            }
        }

        return $config;
    }

    /**
     * Set configuration including group-to-role mappings.
     *
     * @param int   $id
     * @param array $config
     * @param null  $local_config
     * @return mixed
     */
    public static function setConfig($id, $config, $local_config = null)
    {
        $groupRoleMap = array_get($config, 'group_role_map', []);
        unset($config['group_role_map']);

        $result = parent::setConfig($id, $config, $local_config);

        if (isset($groupRoleMap)) {
            RoleGoogle::query()->delete();

            foreach ($groupRoleMap as $map) {
                if (!empty($map['role_id']) && !empty($map['group_email'])) {
                    RoleGoogle::create([
                        'role_id'     => $map['role_id'],
                        'group_email' => $map['group_email'],
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Get the config schema for the admin UI.
     *
     * @return array
     */
    public static function getConfigSchema()
    {
        $schema = parent::getConfigSchema();

        $schema[] = [
            'name'        => 'group_role_map',
            'label'       => 'Google Group to Role Mapping',
            'description' => 'Map Google group memberships to DreamFactory roles. ' .
                             'When enabled, users will be assigned a role based on their Google group membership.',
            'type'        => 'array',
            'required'    => false,
            'allow_null'  => true,
            'items'       => RoleGoogle::getConfigSchema(),
        ];

        return $schema;
    }

    /**
     * Prepare config schema fields with Google-specific labels.
     *
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'map_group_to_role':
                $schema['label'] = 'Map Google Groups to Roles';
                $schema['type'] = 'boolean';
                $schema['default'] = false;
                $schema['description'] = 'Enable mapping of Google group memberships to DreamFactory roles. ' .
                                         'Requires Admin SDK API enabled and domain-wide delegation configured in Google Workspace.';
                break;
        }
    }
}
