<?php

namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\User;
use Log;

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
        if(true){
            $token_a_response = $this->getAccessTokenResponse($this->getCode());
            Log::debug("[OAuth] Got response from Token A request: " . json_encode($token_a_response));
            // exchange OAuth access_token "Token A" for OBO access_token "Token B"
            $tokenA = $this->parseAccessToken($token_a_response);
            Log::debug("[OAuth] Token A: " . $tokenA);
            if(empty($tokenA)) {
                throw new \InvalidArgumentException('Recieved invalid access_token from initial OAuth /token request.');
            }else{
                $graph_response = $this->getGraphTokenResponse($tokenA);
                Log::debug("[OAuth] Got response from Graph Token B request: " . json_encode($graph_response));
                $user = $this->createUserFromGraphTokenResponse($graph_response);

                $obo_response = $this->getOBOTokenResponse($tokenA);
                Log::debug("[OAuth] Got response from Snowflake Token B request: " . json_encode($obo_response));
                return $this->setUserAccessTokenFromResponse($user, $obo_response);
            }
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