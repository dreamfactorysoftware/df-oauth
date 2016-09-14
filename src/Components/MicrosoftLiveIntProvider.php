<?php
namespace DreamFactory\Core\OAuth\Components;

/**
 * Class MicrosoftLiveIntProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class MicrosoftLiveIntProvider extends MicrosoftLiveProvider
{
    protected $authUrl = null;

    protected $tokenUrl = null;

    protected $userUrl = null;

    public function __construct($clientId, $clientSecret, $redirectUrl)
    {
        $this->authUrl = env('MS_LIVE_AUTH_URL', 'https://login.live.com/oauth20_authorize.srf');
        $this->tokenUrl = env('MS_LIVE_TOKEN_URL', 'https://login.live.com/oauth20_token.srf');
        $this->userUrl = env('MS_LIVE_USER_URL', 'https://apis.live.net/v5.0/me?access_token=');

        parent::__construct($clientId, $clientSecret, $redirectUrl);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->authUrl, $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->userUrl . $token);

        return json_decode($response->getBody()->getContents(), true);
    }
}