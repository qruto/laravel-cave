<?php

namespace Qruto\Cave\Http\Responses;

use Illuminate\Http\JsonResponse;
use Qruto\Cave\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Qruto\Cave\Cave;

class EmailVerificationNotificationSentResponse implements EmailVerificationNotificationSentResponseContract
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
                    ? new JsonResponse('', 202)
                    : back()->with('status', Cave::VERIFICATION_LINK_SENT);
    }
}
