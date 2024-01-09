<?php

namespace Qruto\Cave\Actions;

use App\Models\User;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Qruto\Cave\Agent;
use Qruto\Cave\Authenticators\Assertion;
use Qruto\Cave\Authenticators\Attestation;
use Qruto\Cave\Authenticators\InvalidAuthenticatorResponseException;
use Qruto\Cave\Cave;
use Qruto\Cave\Http\Requests\AuthVerifyRequest;
use Qruto\Cave\AuthRateLimiter;
use Qruto\Cave\Models\Passkey;
use Throwable;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredentialCreationOptions;

class AttemptToAuthenticate
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        /**
         * The guard implementation.
         */
        protected readonly StatefulGuard $guard,

        /**
         * The login rate limiter instance.
         */
        protected readonly AuthRateLimiter $limiter,
        protected readonly Attestation $attestation,
        protected readonly Assertion $assertion
    ) {
    }

    /**
     * Handle the incoming request.
     *
     * @return mixed
     */
    public function handle(AuthVerifyRequest $request, callable $next)
    {
        if (Cave::$authenticateUsingCallback) {
            return $this->handleUsingCustomCallback($request, $next);
        }

        $credentials = $request->all();

        try {
            // TODO: rethink state management
            if (session()->has($this->attestation::OPTIONS_SESSION_KEY)) {
                /** @var PublicKeyCredentialCreationOptions $options */
                $options = session($this->attestation::OPTIONS_SESSION_KEY);

                // TODO: abstract user model
                $user = User::whereKey($options->user->id)->firstOrFail();

                if ($user->passkey_verified_at !== null) {
                    // TODO: custom exception
                    throw new Exception('User already verified');
                }

                $publicKeyCredentialSource = $this->attestation->verify(
                    $credentials,
                    $options
                );

                $name = null;

                // TODO: abstract working with agent and make customizable default name
                if ($request->has('name')) {
                    $name = $request->input('name');
                } else {
                    $agent = new Agent();
                    $agent->setUserAgent($request->userAgent());

                    $name = $agent->platform().' ('.$agent->browser().')';
                }

                // TODO: abstract passkey model
                Passkey::createFromSource($publicKeyCredentialSource, $user, $name);
                $user->passkey_verified_at = now();
                $user->save();

                $request->session()->forget($this->attestation::OPTIONS_SESSION_KEY);

                $this->guard->login($user, $request->boolean('remember'));

                return $next($request);
            }

            if (session()->has($this->assertion::OPTIONS_SESSION_KEY)) {
                if ($this->guard->attempt(
                    $credentials,
                    $request->boolean('remember')
                )) {
                    $request->session()->forget($this->assertion::OPTIONS_SESSION_KEY);

                    return $next($request);
                }
            }

            //TODO: custom exception
            $this->throwFailedAuthenticationException($request);
        } catch (AuthenticatorResponseVerificationException|InvalidAuthenticatorResponseException $e) {
            $this->throwFailedAuthenticationException($request, $e);
        }
    }

    /**
     * Attempt to authenticate using a custom callback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    protected function handleUsingCustomCallback($request, $next)
    {
        $user = call_user_func(Cave::$authenticateUsingCallback, $request);

        if (! $user) {
            $this->fireFailedEvent($request);

            return $this->throwFailedAuthenticationException($request);
        }

        $this->guard->login($user, $request->boolean('remember'));

        return $next($request);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function fireFailedEvent($request)
    {
        // TODO: fire all events
        event(new Failed(config('cave.guard'), null, [
            Cave::username() => $request->{Cave::username()},
            'password' => $request->password,
        ]));
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException(
        Request $request,
        $exception = null
    ) {
        $this->limiter->increment($request);

        if ($exception instanceof Throwable) {
            throw $exception;
        }

        if (is_string($exception)) {
            throw new $exception(trans('auth.failed'));
        }

        throw new AuthenticationException(trans('auth.failed'), [$this->guard->name], route('auth'));
    }
}
