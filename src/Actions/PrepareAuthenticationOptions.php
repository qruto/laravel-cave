<?php

namespace Qruto\Cave\Actions;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Response;
use Qruto\Cave\Authenticators\Assertion;
use Qruto\Cave\Authenticators\Attestation;
use Qruto\Cave\Cave;
use Qruto\Cave\Contracts\CreatesNewUsers;
use Qruto\Cave\Http\Requests\AuthOptionsRequest;
use Qruto\Cave\Models\User;

class PrepareAuthenticationOptions
{
    public function __construct(
        private readonly Attestation $attestation,
        private readonly Assertion $assertion,
        private readonly StatefulGuard $guard,
        private readonly CreatesNewUsers $creator,
    ) {
    }

    public function handle($request, $next)
    {
        $model = $this->guard->getProvider()->getModel();

        $user = $model::firstOrNew([Cave::username() => $request->{Cave::username()}]);

        if ($user->exists && $user->passkey_verified_at !== null) {
            return $next($this->assertion($user, $request));
        }

        return $next($this->attestation($user, $request));
    }

    private function assertion(User $user, AuthOptionsRequest $request)
    {
        $options = $this->assertion->newOptions($user);

        if ($request->session()->has($this->attestation::OPTIONS_SESSION_KEY)) {
            $request->session()->forget($this->attestation::OPTIONS_SESSION_KEY);
        }

        $request->session()->put(
            $this->assertion::OPTIONS_SESSION_KEY,
            $options
        );

        return response()->json($options);
    }

    private function attestation(User $user, AuthOptionsRequest $request)
    {
        if (! $user->exists) {
            $this->creator->create($request->all(), $user);
            $user->save();
        }

        if ($request->session()->has($this->assertion::OPTIONS_SESSION_KEY)) {
            $request->session()->forget($this->assertion::OPTIONS_SESSION_KEY);
        }

        $options = $this->attestation->newOptions($user);

        $request->session()->put(
            $this->attestation::OPTIONS_SESSION_KEY,
            $options
        );

        return response()->json($options, Response::HTTP_CREATED);
    }
}
