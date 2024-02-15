<?php

namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\User;
use Log;

/**
 * Trait DfOAuthTwoOboProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 * 
 * Implementation of Microsoft OAuth 2.0 On-Behalf-Of (OBO) flow
 * https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-on-behalf-of-flow
 */
trait DfOAuthTwoOboProvider
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
        $token_a_response = $this->getAccessTokenResponse($this->getCode());
        
        $tokenA = $this->parseAccessToken($token_a_response);
        if(empty($tokenA)) {
            throw new \InvalidArgumentException('Recieved invalid access_token from initial OAuth oauth2/v2.0/token request.');
        }else{
            // exchange OAuth access_token "Token A" for OBO access_token "Token B"
            // Need separate access_token for each Microsoft Entra API resource
            $graph_token_response = $this->getGraphTokenResponse($tokenA);
            $user = $this->createUserFromGraphTokenResponse($graph_token_response);

            $database_token_response = $this->getDatabaseTokenResponse($tokenA);
            return $this->setUserAccessTokenFromResponse($user, $database_token_response);
        }
    }
    
    /**
     * Retrieve user using token response.
     *
     * @param $response
     *
     * @return $this
     */
    public function createUserFromGraphTokenResponse($response)
    {
        return $this->mapUserToObject($this->getUserByToken($this->parseAccessToken($response)));
    }

    /**
     * Retrieve user using token response.
     *
     * @param $response
     *
     * @return $this
     */
    public function setUserAccessTokenFromResponse($user, $response)
    {
        $token = $this->parseAccessToken($response);

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
        return Arr::get($body, 'access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseRefreshToken($body)
    {
        return Arr::get($body, 'refresh_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseExpiresIn($body)
    {
        return Arr::get($body, 'expires_in');
    }
}