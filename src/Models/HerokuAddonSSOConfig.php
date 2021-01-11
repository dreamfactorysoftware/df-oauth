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
        'secret_as_file',
    ];

    protected $encrypted = ['secret'];

    protected $protected = ['secret'];

    protected $casts = [
        'service_id' => 'integer',
        'secret' => 'string',
        'secret_as_file' => 'boolean',
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
            case 'secret_as_file':
                $schema['label'] = 'Secret as a file?';
                $schema['description'] = 'Use a secret field as a path to a secret file?';
                $schema['type'] = 'boolean';
                break;
        }
    }
}
