<?php
namespace DreamFactory\Core\OAuth\Components;

use SocialiteProviders\Live\Provider as MSLive;
use Illuminate\Http\Request;

/**
 * Class MicrosoftLiveProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class MicrosoftLiveProvider extends MSLive
{
    use DfOAuthTwoProvider;

    /**
     * @param Request $clientId
     * @param string  $clientSecret
     * @param string  $redirectUrl
     */
    public function __construct($clientId, $clientSecret, $redirectUrl)
    {
        /** @var Request $request */
        $request = \Request::instance();
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }
}