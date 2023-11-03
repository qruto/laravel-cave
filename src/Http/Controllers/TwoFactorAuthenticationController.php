<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\Cave\Actions\DisableTwoFactorAuthentication;
use Qruto\Cave\Actions\EnableTwoFactorAuthentication;
use Qruto\Cave\Contracts\TwoFactorDisabledResponse;
use Qruto\Cave\Contracts\TwoFactorEnabledResponse;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Enable two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Qruto\Cave\Actions\EnableTwoFactorAuthentication  $enable
     * @return \Qruto\Cave\Contracts\TwoFactorEnabledResponse
     */
    public function store(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $enable($request->user());

        return app(TwoFactorEnabledResponse::class);
    }

    /**
     * Disable two factor authentication for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Qruto\Cave\Actions\DisableTwoFactorAuthentication  $disable
     * @return \Qruto\Cave\Contracts\TwoFactorDisabledResponse
     */
    public function destroy(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $disable($request->user());

        return app(TwoFactorDisabledResponse::class);
    }
}
