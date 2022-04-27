<?php

namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Trait DfOAuthTwoProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
trait DfOAuthTwoProvider
{
    /** @var null|string */
    protected $state = null;

    /** {@inheritdoc} */
    abstract protected function getUserByToken($token);

    /** {@inheritdoc} */
    abstract protected function getCode();

    /** {@inheritdoc} */
    abstract public function usesState();

    /** {@inheritdoc} */
    abstract public function isStateless();

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $state = null;
        if ($this->usesState()) {
            $state = Str::random(40);
            $this->state = $state;
            \Cache::put($state, $state, 180);
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * Returns state identifier.
     *
     * @return null|string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasInvalidState()
    {
        if ($this->isStateless()) {
            return false;
        }
        $urlState = $this->request->input('state');
        $cacheState = \Cache::pull($urlState);

        return !(strlen($cacheState) > 0 && $urlState === $cacheState);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        return $this->getUserFromTokenResponse($response);
    }

    /**
     * Retrieve user using token response.
     *
     * @param $response
     *
     * @return $this
     */
    public function getUserFromTokenResponse($response)
    {
        $user = $this->mapUserToObject($this->getUserByToken(
            $token = $this->parseAccessToken($response)
        ));

        $this->credentialsResponseBody = $response;

        if ($user instanceof User) {
            $user->setAccessTokenResponseBody($this->credentialsResponseBody);
        }

        return $user->setToken($token)
            ->setRefreshToken($this->parseRefreshToken($response))
            ->setExpiresIn($this->parseExpiresIn($response));
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return array_get($body, 'access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseRefreshToken($body)
    {
        return array_get($body, 'refresh_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseExpiresIn($body)
    {
        return array_get($body, 'expires_in');
    }
}