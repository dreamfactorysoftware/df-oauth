<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth1\AbstractProvider;
use SocialiteProviders\Manager\OAuth1\User;

/**
 * Class BitbucketProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class BitbucketProvider extends AbstractProvider
{
    use DfOAuthOneProvider;

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'BITBUCKET';

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user['extra'])->map([
            'id'       => $user['uid'],
            'nickname' => $user['nickname'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['imageUrl'],
        ]);
    }

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