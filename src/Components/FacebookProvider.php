<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth2\User;
use Illuminate\Support\Arr;

/**
 * Class FacebookProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class FacebookProvider extends \Laravel\Socialite\Two\FacebookProvider
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
        $avatarUrl = $this->graphUrl . '/' . $this->version . '/' . $user['id'] . '/picture';

        return (new User)->setRaw($user)->map([
            'id'              => $user['id'],
            'nickname'        => null,
            'name'            => Arr::get($user, 'name'),
            'email'           => Arr::get($user, 'email'),
            'avatar'          => $avatarUrl . '?type=normal',
            'avatar_original' => $avatarUrl . '?width=1920',
        ]);
    }
}