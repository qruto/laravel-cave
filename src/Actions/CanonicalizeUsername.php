<?php

namespace Qruto\Cave\Actions;

use Illuminate\Support\Str;
use Qruto\Cave\Cave;

class CanonicalizeUsername
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $request->merge([
            Cave::username() => Str::lower($request->{Cave::username()}),
        ]);

        return $next($request);
    }
}
