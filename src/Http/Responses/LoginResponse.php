<?php

namespace Qruto\Cave\Http\Responses;

use Qruto\Cave\Contracts\LoginResponse as LoginResponseContract;
use Qruto\Cave\Cave;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
                    ? response()->json(['two_factor' => false])
                    : redirect()->intended(Cave::redirects('login'));
    }
}
