<?php
namespace DreamFactory\Core\OAuth\Components;

use GuzzleHttp\Exception\BadResponseException;
use SocialiteProviders\Twitter\Server as BaseServer;
use League\OAuth1\Client\Credentials\TemporaryCredentials;

/**
 * Class TwitterServer
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class TwitterServer extends BaseServer
{

    /**
     * {@inheritdoc}
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