<?php
namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Models\BaseModel;

/**
 * Class OAuthConfig
 *
 * @package DreamFactory\Core\OAuth\Models
 */
class HerokuAddonUser extends BaseModel
{
    protected $table = 'heroku_users_map';

    protected $fillable = [
        'user_id',
        'heroku_user_id',
    ];

}
