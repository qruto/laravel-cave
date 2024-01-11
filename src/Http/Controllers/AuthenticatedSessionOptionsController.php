<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Qruto\Cave\Actions\CanonicalizeUsername;
use Qruto\Cave\Actions\EnsureAuthIsNotThrottled;
use Qruto\Cave\Actions\PrepareAuthenticationOptions;
use Qruto\Cave\AuthOptionsVerificationRateLimiter;
use Qruto\Cave\AuthRateLimiter;
use Qruto\Cave\Cave;
use Qruto\Cave\Http\Requests\AuthOptionsRequest;

class AuthenticatedSessionOptionsController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected readonly Container $container,
    ) {
        $this->container->bind(
            AuthRateLimiter::class,
            AuthOptionsVerificationRateLimiter::class
        );
    }

    public function store(AuthOptionsRequest $request)
    {
        // TODO: custom response
        return $this->authOptionsPipeline($request)->then(fn ($response
        ) => $response);
    }

    protected function authOptionsPipeline(
        AuthOptionsRequest $request
    ) {
        // TODO: change custom auth pipeline handling
        if (Cave::$authenticateThroughCallback) {
            return (new Pipeline($this->container))->send($request)->through(array_filter(
                call_user_func(Cave::$authenticateThroughCallback, $request)
            ));
        }

        return (new Pipeline($this->container))->send($request)->through(array_filter([
            // TODO: change custom auth pipeline handling
            config('cave.limiters.auth') ? null : EnsureAuthIsNotThrottled::class,
            config('cave.lowercase_usernames') ? CanonicalizeUsername::class : null,
            PrepareAuthenticationOptions::class,
        ]));
    }
}
