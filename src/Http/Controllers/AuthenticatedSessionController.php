<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Pipeline;
use Qruto\Cave\Actions\AttemptToAuthenticate;
use Qruto\Cave\Actions\CanonicalizeUsername;
use Qruto\Cave\Actions\EnsureLoginIsNotThrottled;
use Qruto\Cave\Actions\PrepareAuthenticatedSession;
use Qruto\Cave\Cave;
use Qruto\Cave\Contracts\AuthResponse;
use Qruto\Cave\Contracts\AuthViewResponse;
use Qruto\Cave\Contracts\LogoutResponse;
use Qruto\Cave\Http\Requests\AuthVerifyRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $guard;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Show the auth view.
     */
    public function create(Request $request): AuthViewResponse
    {
        return app(AuthViewResponse::class);
    }

    /**
     * Attempt to authenticate a new session.
     *
     * @return mixed
     */
    public function store(AuthVerifyRequest $request)
    {
        return $this->authPipeline($request)->then(function ($request) {
            return app(AuthResponse::class);
        });
    }

    /**
     * Get the authentication pipeline instance.
     *
     * @return \Illuminate\Pipeline\Pipeline
     */
    protected function authPipeline(
        AuthVerifyRequest $request
    ) {
        if (Cave::$authenticateThroughCallback) {
            return (new Pipeline(app()))->send($request)->through(array_filter(
                call_user_func(Cave::$authenticateThroughCallback, $request)
            ));
        }

        if (is_array(config('cave.pipelines.login'))) {
            return (new Pipeline(app()))->send($request)->through(array_filter(
                config('cave.pipelines.login')
            ));
        }

        return (new Pipeline(app()))->send($request)->through(array_filter([
            config('cave.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            config('cave.lowercase_usernames') ? CanonicalizeUsername::class : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): LogoutResponse
    {
        $this->guard->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return app(LogoutResponse::class);
    }
}
