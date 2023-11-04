<?php

namespace Qruto\Cave\Http\Responses;

use Illuminate\Http\JsonResponse;
use Qruto\Cave\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Qruto\Cave\Cave;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
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
                    ? new JsonResponse('', 204)
                    : redirect()->intended(Cave::redirects('login'));
    }
}
