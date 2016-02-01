<?php
namespace DreamFactory\Core\OAuth\Components;

use League\OAuth1\Client\Credentials\TemporaryCredentials;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait DfOAuthOneProvider
{
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
}