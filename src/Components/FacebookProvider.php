<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;

/**
 * Class FacebookProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class FacebookProvider extends \Laravel\Socialite\Two\FacebookProvider
{
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