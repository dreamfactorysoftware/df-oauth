<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class GoogleProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class GoogleProvider extends \Laravel\Socialite\Two\GoogleProvider
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

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => array_get($user, 'nickname'),
            'name'     => $user['displayName'],
            'email'    => $user['emails'][0]['value'],
            'avatar'   => array_get($user, 'image')['url'],
        ]);
    }
}