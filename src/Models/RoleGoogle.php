<?php

namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Models\BaseModel;
use DreamFactory\Core\Models\Role;

class RoleGoogle extends BaseModel
{
    protected $table = 'role_google';

    protected $primaryKey = 'role_id';

    protected $fillable = ['role_id', 'group_email'];

    public $timestamps = false;

    public $incrementing = false;

    /**
     * Get the config schema for the admin UI.
     *
     * @return array
     */
    public static function getConfigSchema()
    {
        $roles = Role::whereIsActive(1)->get();
        $roleList = [];

        foreach ($roles as $role) {
            $roleList[] = [
                'label' => $role->name,
                'name'  => $role->id
            ];
        }

        return [
            [
                'name'        => 'role_id',
                'label'       => 'Role',
                'type'        => 'picklist',
                'required'    => true,
                'values'      => $roleList,
                'description' => 'Select the DreamFactory role to assign.'
            ],
            [
                'name'        => 'group_email',
                'label'       => 'Google Group Email',
                'type'        => 'string',
                'required'    => true,
                'description' => 'Enter the email address of the Google group (e.g., developers@example.com).'
            ],
        ];
    }
}
