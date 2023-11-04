<?php

namespace Qruto\Cave\Http\Responses;

use Illuminate\Http\JsonResponse;
use Qruto\Cave\Contracts\LogoutResponse as LogoutResponseContract;
use Qruto\Cave\Cave;

class LogoutResponse implements LogoutResponseContract
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
                    : redirect(Cave::redirects('logout', '/'));
    }
}
