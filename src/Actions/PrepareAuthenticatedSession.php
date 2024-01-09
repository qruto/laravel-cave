<?php

namespace Qruto\Cave\Actions;

use Qruto\Cave\AuthRateLimiter;

class PrepareAuthenticatedSession
{
    /**
     * The login rate limiter instance.
     *
     * @var \Qruto\Cave\AuthRateLimiter
     */
    protected $limiter;

    /**
     * Create a new class instance.
     *
     * @param  \Qruto\Cave\AuthRateLimiter  $limiter
     * @return void
     */
    public function __construct(AuthRateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->limiter->clear($request);

        return $next($request);
    }
}
