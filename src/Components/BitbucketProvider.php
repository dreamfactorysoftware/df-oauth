<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use League\OAuth1\Client\Server\Bitbucket as BitbucketServer;

/**
 * Class TwitterProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class BitbucketProvider extends \Laravel\Socialite\One\BitbucketProvider
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
        parent::__construct($request, new BitbucketServer($serverConfig));
    }
}