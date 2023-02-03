<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth2\User;
use Illuminate\Support\Arr;

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
        $avatarUrl = Arr::get($user, 'picture');

        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'nickname'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => $avatarUrl,
            'avatar_original' => preg_replace('/\?sz=([0-9]+)/', '', $avatarUrl),
        ]);
    }
}