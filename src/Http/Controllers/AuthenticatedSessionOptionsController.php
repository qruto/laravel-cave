<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Qruto\Cave\Authenticators\Assertion;
use Qruto\Cave\Authenticators\Attestation;
use Qruto\Cave\Cave;
use Qruto\Cave\Contracts\CreatesNewUsers;
use Qruto\Cave\Http\Requests\AuthOptionsRequest;

class AuthenticatedSessionOptionsController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        private readonly Attestation $attestation,
        private readonly Assertion $assertion,
        private readonly StatefulGuard $guard,
        private readonly CreatesNewUsers $creator
    ) {
    }

    public function store(AuthOptionsRequest $request)
    {
        $model = $this->guard->getProvider()->getModel();

        if (config('fortify.lowercase_usernames')) {
            $request->merge([
                Cave::username() => Str::lower($request->{Cave::username()}),
            ]);
        }

        $user = $model::firstOrNew([Cave::username() => $request->{Cave::username()}]);

        if ($user->exists && $user->passkey_verified_at !== null) {
            return $this->assertionOptions($user, $request);
        }

        return $this->attestationOptions($user, $request);
    }

    private function assertionOptions($user, AuthOptionsRequest $request)
    {
        $options = $this->assertion->newOptions($user);

        $request->session()->put(
            $this->assertion::OPTIONS_SESSION_KEY,
            $options
        );

        return response()->json($options);
    }

    private function attestationOptions($user, AuthOptionsRequest $request)
    {
        $this->creator->create($request->all(), $user);

        if (! $user->exists) {
            $user->save();
        }

        $options = $this->attestation->newOptions($user);

        $request->session()->put(
            $this->attestation::OPTIONS_SESSION_KEY,
            $options
        );

        return response()->json($options, Response::HTTP_CREATED);
    }
}
