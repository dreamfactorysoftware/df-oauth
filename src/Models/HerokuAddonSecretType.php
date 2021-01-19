<?php


namespace DreamFactory\Core\OAuth\Models;


use DreamFactory\Core\Enums\FactoryEnum;

class HerokuAddonSecretType extends FactoryEnum
{
    // Use secret as is
    const STRING = 'string';
    // Use secret as path to file with secret
    const FILE = 'file';
    // Use secret as env variable name with secret
    const ENVIRONMENT = 'environment';
}
