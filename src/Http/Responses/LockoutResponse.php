<?php

namespace Qruto\Cave\Http\Responses;

use Illuminate\Http\Response;
use Qruto\Cave\AuthRateLimiter;
use Qruto\Cave\Contracts\LockoutResponse as LockoutResponseContract;
use Qruto\Cave\Http\Requests\AuthVerifyRequest;

class LockoutResponse implements LockoutResponseContract
{
    /**
     * The login rate limiter instance.
     *
     * @var \Qruto\Cave\AuthRateLimiter
     */
    protected $limiter;

    /**
     * Create a new response instance.
     *
     * @return void
     */
    public function __construct(AuthRateLimiter $limiter)
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
        return with($this->limiter->availableIn(AuthVerifyRequest::createFrom($request)),
            function ($seconds) {
                return response(trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]), Response::HTTP_TOO_MANY_REQUESTS);
            });
    }
}
