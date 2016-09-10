<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class GithubProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class GithubProvider extends \Laravel\Socialite\Two\GithubProvider
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
            'nickname' => $user['login'],
            'name'     => array_get($user, 'name'),
            'email'    => array_get($user, 'email'),
            'avatar'   => $user['avatar_url'],
        ]);
    }
}