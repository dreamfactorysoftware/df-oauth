<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Twitter\Provider;

/**
 * Class TwitterProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class TwitterProvider extends Provider
{
    use DfOAuthOneProvider;

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUrl
     */
    public function __construct($clientId, $clientSecret, $redirectUrl)
    {
        /** @var Request $request */
        $request = \Request::instance();
        $serverConfig = [
            'identifier'   => $clientId,
            'secret'       => $clientSecret,
            'callback_uri' => $redirectUrl
        ];
        parent::__construct($request, new TwitterServer($serverConfig));
    }
}