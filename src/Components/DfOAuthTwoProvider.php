<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;
use DreamFactory\Core\OAuth\Components\DfOAuthTwoUser as User;

trait DfOAuthTwoProvider
{
    /** @var  \Request */
    protected $request;

    /** @var  array */
    protected $credentialsResponseBody;
    /**
     * {@inheritdoc}
     */
    abstract public function usesState();

    /**
     * {@inheritdoc}
     */
    abstract public function isStateless();

    /**
     * {@inheritdoc}
     */
    abstract protected function getCode();


    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $state = null;
        if ($this->usesState()) {
            $state = Str::random(40);
            \Cache::put($state, $state, 3);
        }

        return new RedirectResponse($this->getAuthUrl($state));
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
     * @return User
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

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
     * Get the access token from the token response body.
     *
     * @param string $body
     *
     * @return string
     */
    protected function parseAccessToken($body)
    {
        return array_get($body, 'access_token');
    }

    /**
     * Get the refresh token from the token response body.
     *
     * @param string $body
     *
     * @return string
     */
    protected function parseRefreshToken($body)
    {
        return array_get($body, 'refresh_token');
    }

    /**
     * Get the expires in from the token response body.
     *
     * @param string $body
     *
     * @return string
     */
    protected function parseExpiresIn($body)
    {
        return array_get($body, 'expires_in');
    }
}