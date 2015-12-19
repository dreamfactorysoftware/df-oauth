<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait DfOAuthTwoProvider
{
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