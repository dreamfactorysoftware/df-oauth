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
        $avatarUrl = array_get($user, 'picture');

        return (new User)->setRaw($user)->map([
            'id'       => array_get($user, 'id'),
            'nickname' => array_get($user, 'nickname'),
            'name' => array_get($user, 'name'),
            'email' => array_get($user, 'email'),
            'avatar' => $avatarUrl,
            'avatar_original' => preg_replace('/\?sz=([0-9]+)/', '', $avatarUrl),
        ]);
    }
}