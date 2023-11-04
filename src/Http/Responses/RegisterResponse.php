<?php

namespace Qruto\Cave\Http\Responses;

use Illuminate\Http\JsonResponse;
use Qruto\Cave\Contracts\RegisterResponse as RegisterResponseContract;
use Qruto\Cave\Cave;

class RegisterResponse implements RegisterResponseContract
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
                    ? new JsonResponse('', 201)
                    : redirect()->intended(Cave::redirects('register'));
    }
}
