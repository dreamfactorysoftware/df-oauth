<?php

namespace DreamFactory\Core\OAuth\Components;

use League\OAuth1\Client\Credentials\TemporaryCredentials;
use Symfony\Component\HttpFoundation\RedirectResponse;
use League\OAuth1\Client\Server\Twitter as TwitterServer;

/**
 * Trait DfOAuthOneProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
trait DfOAuthOneProvider
{
    /** {@inheritdoc} */
    protected function isStateless()
    {
        if (defined('SOCIALITEPROVIDERS_STATELESS')) {
            return true;
        }

        return $this->stateless;
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        if (!$this->isStateless()) {
            $temp = $this->server->getTemporaryCredentials();
            \Cache::put('oauth.temp', $temp, 3);
        } else {
            $temp = $this->server->getTemporaryCredentials();
            \Cache::put('oauth_temp', serialize($temp), 3);
        }

        return new RedirectResponse($this->server->getAuthorizationUrl($temp));
    }

    /**
     * Returns oauth token used in last authorization process.
     *
     * @return string
     */
    public function getOAuthToken()
    {
        if (!$this->isStateless()) {
            return \Cache::get('oauth.temp');
        } else {
            /** @var TemporaryCredentials $temp */
            $temp = unserialize(\Cache::get('oauth_temp'));

            return $temp->getIdentifier();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getToken()
    {
        if (!$this->isStateless()) {
            $temp = \Cache::pull('oauth.temp');

            return $this->server->getTokenCredentials(
                $temp, $this->request->get('oauth_token'), $this->request->get('oauth_verifier')
            );
        } else {
            $temp = unserialize(\Cache::pull('oauth_temp'));

            return $this->server->getTokenCredentials(
                $temp, $this->request->get('oauth_token'), $this->request->get('oauth_verifier')
            );
        }
    }
}