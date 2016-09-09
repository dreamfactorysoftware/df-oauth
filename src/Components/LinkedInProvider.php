<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use DreamFactory\Core\OAuth\Components\DfOAuthTwoUser as User;

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
            'id' => $user['id'], 'nickname' => null, 'name' => array_get($user, 'formattedName'),
            'email' => array_get($user, 'emailAddress'), 'avatar' => array_get($user, 'pictureUrl'),
            'avatar_original' => array_get($user, 'pictureUrls.values.0'),
        ]);
    }
}