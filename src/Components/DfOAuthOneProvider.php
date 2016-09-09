<?php
namespace DreamFactory\Core\OAuth\Components;

use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use Symfony\Component\HttpFoundation\RedirectResponse;
use League\OAuth1\Client\Server\Twitter as TwitterServer;
use SocialiteProviders\Manager\OAuth1\User;
use League\OAuth1\Client\Credentials\CredentialsException;

trait DfOAuthOneProvider
{
    /** @var  TwitterServer */
    protected $server;

    /** @var  \Request */
    protected $request;

    abstract protected function mapUserToObject(array $user);

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        /** @type TemporaryCredentials $temp */
        $temp = $this->server->getTemporaryCredentials();

        $identifier = $temp->getIdentifier();
        \Cache::put($identifier, $temp, 3);

        return new RedirectResponse($this->server->getAuthorizationUrl($temp));
    }

    /**
     * {@inheritdoc}
     */
    protected function getToken()
    {
        $key = $this->request->get('oauth_token');
        $temp = \Cache::pull($key);

        return $this->server->getTokenCredentials(
            $temp, $this->request->get('oauth_token'), $this->request->get('oauth_verifier')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if (!$this->hasNecessaryVerifier()) {
            throw new \InvalidArgumentException('Invalid request. Missing OAuth verifier.');
        }

        $token = $this->getToken();
        /** @var TokenCredentials $tokenCredentials */
        $tokenCredentials = $token['tokenCredentials'];

        /** @var User $user */
        $user = $this->mapUserToObject((array) $this->server->getUserDetails($tokenCredentials));

        $user->setToken($tokenCredentials->getIdentifier(), $tokenCredentials->getSecret());

        if ($user instanceof User) {
            parse_str($token['credentialsResponseBody'], $credentialsResponseBody);

            if (!$credentialsResponseBody || !is_array($credentialsResponseBody)) {
                throw new CredentialsException('Unable to parse token credentials response.');
            }

            $user->setAccessTokenResponseBody($credentialsResponseBody);
        }

        return $user;
    }

    /**
     * Determine if the request has the necessary OAuth verifier.
     *
     * @return bool
     */
    protected function hasNecessaryVerifier()
    {
        return $this->request->has('oauth_token') && $this->request->has('oauth_verifier');
    }
}