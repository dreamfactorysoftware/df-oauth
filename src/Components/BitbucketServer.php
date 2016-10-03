<?php
namespace DreamFactory\Core\OAuth\Components;

use SocialiteProviders\Manager\OAuth1\Server;
use League\OAuth1\Client\Credentials\TokenCredentials;
use SocialiteProviders\Manager\OAuth1\User;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Class BitbucketServer
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class BitbucketServer extends Server
{
    /**
     * {@inheritDoc}
     */
    public function urlTemporaryCredentials()
    {
        return 'https://bitbucket.org/api/1.0/oauth/request_token';
    }

    /**
     * {@inheritDoc}
     */
    public function urlAuthorization()
    {
        return 'https://bitbucket.org/api/1.0/oauth/authenticate';
    }

    /**
     * {@inheritDoc}
     */
    public function urlTokenCredentials()
    {
        return 'https://bitbucket.org/api/1.0/oauth/access_token';
    }

    /**
     * {@inheritDoc}
     */
    public function urlUserDetails()
    {
        return 'https://bitbucket.org/api/1.0/user';
    }

    /**
     * {@inheritDoc}
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        $user = new User();

        $user->uid = $data['user']['username'];
        $user->nickname = $data['user']['username'];
        $user->name = $data['user']['display_name'];
        $user->firstName = $data['user']['first_name'];
        $user->lastName = $data['user']['last_name'];
        $user->imageUrl = $data['user']['avatar'];

        $used = array('username', 'display_name', 'avatar');

        foreach ($data as $key => $value) {
            if (strpos($key, 'url') !== false) {
                if (!in_array($key, $used)) {
                    $used[] = $key;
                }

                $user->urls[$key] = $value;
            }
        }

        // Save all extra data
        $user->extra = array_diff_key($data, array_flip($used));

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return $data['user']['username'];
    }

    /**
     * {@inheritDoc}
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return $data['user']['display_name'];
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenCredentials(TemporaryCredentials $temporaryCredentials, $temporaryIdentifier, $verifier)
    {
        if ($temporaryIdentifier !== $temporaryCredentials->getIdentifier()) {
            throw new \InvalidArgumentException(
                'Temporary identifier passed back by server does not match that of stored temporary credentials.
                Potential man-in-the-middle.'
            );
        }

        $uri = $this->urlTokenCredentials();
        $bodyParameters = array('oauth_verifier' => $verifier);

        $client = $this->createHttpClient();

        $headers = $this->getHeaders($temporaryCredentials, 'POST', $uri, $bodyParameters);

        try {
            $response = $client->post($uri, [
                'headers'     => $headers,
                'form_params' => $bodyParameters,
            ]);
        } catch (BadResponseException $e) {
            return $this->handleTokenCredentialsBadResponse($e);
        }

        return [
            'tokenCredentials'        => $this->createTokenCredentials((string)$response->getBody()),
            'credentialsResponseBody' => $response->getBody(),
        ];
    }
}