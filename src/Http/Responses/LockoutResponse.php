<?php

namespace Qruto\Cave\Http\Responses;

use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Qruto\Cave\Contracts\LockoutResponse as LockoutResponseContract;
use Qruto\Cave\Cave;
use Qruto\Cave\LoginRateLimiter;

class LockoutResponse implements LockoutResponseContract
{
    /**
     * The login rate limiter instance.
     *
     * @var \Qruto\Cave\LoginRateLimiter
     */
    protected $limiter;

    /**
     * Create a new response instance.
     *
     * @param  \Qruto\Cave\LoginRateLimiter  $limiter
     * @return void
     */
    public function __construct(LoginRateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return with($this->limiter->availableIn($request), function ($seconds) {
            throw ValidationException::withMessages([
                Cave::username() => [
                    trans('auth.throttle', [
                        'seconds' => $seconds,
                        'minutes' => ceil($seconds / 60),
                    ]),
                ],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        });
    }
}
