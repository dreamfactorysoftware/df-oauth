<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth2\User;
use Illuminate\Support\Arr;

/**
 * Class LinkedInProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class LinkedInProvider extends \Laravel\Socialite\Two\LinkedInProvider
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
            'id'              => $user['id'],
            'nickname'        => null,
            'name'            => Arr::get($user, 'formattedName'),
            'email'           => Arr::get($user, 'emailAddress'),
            'avatar'          => Arr::get($user, 'pictureUrl'),
            'avatar_original' => Arr::get($user, 'pictureUrls.values.0'),
        ]);
    }
}