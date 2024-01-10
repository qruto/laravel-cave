<?php

namespace Qruto\Cave\Actions;

use Illuminate\Auth\Events\Lockout;
use Qruto\Cave\AuthRateLimiter;
use Qruto\Cave\Contracts\LockoutResponse;

class EnsureAuthIsNotThrottled
{
    /**
     * The login rate limiter instance.
     *
     * @var \Qruto\Cave\AuthVerificationRateLimiter
     */
    protected $limiter;

    /**
     * Create a new class instance.
     *
     * @param  \Qruto\Cave\AuthVerificationRateLimiter  $limiter
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
        if (! $this->limiter->tooManyAttempts($request)) {
            return $next($request);
        }

        event(new Lockout($request));

        return app(LockoutResponse::class);
    }
}
