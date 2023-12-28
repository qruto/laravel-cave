<?php

namespace Qruto\Cave\Http\Responses;

use Qruto\Cave\Cave;
use Qruto\Cave\Contracts\AuthResponse as AuthResponseContract;

class AuthResponse implements AuthResponseContract
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
