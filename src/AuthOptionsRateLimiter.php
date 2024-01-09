<?php

namespace Qruto\Cave;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthOptionsRateLimiter extends AuthRateLimiter
{
    /** Get the throttle key for the given request. */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input(Cave::username())).'|'.$request->ip());
    }
}
