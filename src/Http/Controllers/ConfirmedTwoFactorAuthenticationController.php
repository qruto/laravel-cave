<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\Cave\Actions\ConfirmTwoFactorAuthentication;
use Qruto\Cave\Contracts\TwoFactorConfirmedResponse;

class ConfirmedTwoFactorAuthenticationController extends Controller
{
    /**
     * Enable two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Qruto\Cave\Actions\ConfirmTwoFactorAuthentication  $confirm
     * @return \Qruto\Cave\Contracts\TwoFactorConfirmedResponse
     */
    public function store(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $confirm($request->user(), $request->input('code'));

        return app(TwoFactorConfirmedResponse::class);
    }
}
