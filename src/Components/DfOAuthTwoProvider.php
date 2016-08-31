<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait DfOAuthTwoProvider
{
    /** @var  \Request */
    protected $request;

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
}