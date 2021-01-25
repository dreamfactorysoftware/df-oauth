<?php
namespace DreamFactory\Core\OAuth\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OAuthConfig
 *
 * @package DreamFactory\Core\OAuth\Models
 */
class HerokuAddonSSOConfig extends BaseServiceConfigModel
{
    protected $table = 'heroku_addon_sso';

    protected $fillable = [
        'service_id',
        'secret',
        'secret_type',
    ];

    protected $encrypted = ['secret'];

    protected $protected = ['secret'];

    protected $casts = [
        'service_id' => 'integer',
        'secret' => 'string',
        'secret_type' => 'string',
    ];

    protected $rules = [
        'secret' => 'required',
    ];

    /**
     * @return BelongsTo
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
            case 'secret':
                $schema['label'] = 'Secret';
                $schema['description'] = 'Salt that will be used to verify the token';
                $schema['type'] = 'string';
                break;
            case 'secret_type':
                $schema['label'] = 'Secret type';
                $schema['description'] = 'Define how to use secret value';
                $schema['type'] = 'picklist';
                $schema['values'] = array_map(
                    function($k, $v) {return ['label' => $k, 'name' => $v];},
                    array_keys(HerokuAddonSecretType::all()), HerokuAddonSecretType::all()
                );
                break;
        }
    }
}
