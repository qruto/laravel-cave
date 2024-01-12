<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\Cave\Actions\ConfirmPassword;
use Qruto\Cave\Contracts\ConfirmPasskeyViewResponse;
use Qruto\Cave\Contracts\FailedPasswordConfirmationResponse;
use Qruto\Cave\Contracts\PasswordConfirmedResponse;

class ConfirmablePasswordController extends Controller
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
        protected StatefulGuard $guard,
    ) {
    }

    /**
     * Show the confirm password view.
     *
     * @return \Qruto\Cave\Contracts\ConfirmPasskeyViewResponse
     */
    public function show(Request $request, ConfirmPasskeyViewResponse $response)
    {
        return $response;
    }

    /**
     * Confirm the user's password.
     *
     * @return \Illuminate\Contracts\Support\Responsable
     */
    public function store(Request $request)
    {
        $confirmed = app(ConfirmPassword::class)(
            $this->guard, $request->user(), $request->input('password')
        );

        if ($confirmed) {
            $request->session()->put('auth.passkey_confirmed_at', time());
        }

        return $confirmed
            ? app(PasswordConfirmedResponse::class)
            : app(FailedPasswordConfirmationResponse::class);
    }
}
