<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\Cave\Contracts\VerifyEmailViewResponse;
use Qruto\Cave\Cave;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Qruto\Cave\Contracts\VerifyEmailViewResponse
     */
    public function __invoke(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(Cave::redirects('email-verification'))
                    : app(VerifyEmailViewResponse::class);
    }
}
