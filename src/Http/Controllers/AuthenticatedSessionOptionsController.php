<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Qruto\Cave\Cave;
use Qruto\Cave\Http\Requests\AuthOptionsRequest;

class AuthenticatedSessionOptionsController
{
    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $guard;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    public function create(AuthOptionsRequest $request)
    {
        $model = $this->guard->getProvider()->getModel();

        $user = $model::firstOrNew([Cave::username() => $request->{Cave::username()}]);


    }
}
