<?php

namespace Qruto\Cave\Actions;

use Illuminate\Contracts\Auth\StatefulGuard;
use Qruto\Cave\Cave;
use Qruto\Cave\Contracts\WebAuthenticatable;

class ConfirmPasskey
{
    /**
     * Confirm that the given password is valid for the given user.
     */
    public function __invoke(
        StatefulGuard $guard,
        WebAuthenticatable $user,
        array $credentials
    ): bool {
        return is_null(Cave::$confirmPasswordsUsingCallback)
            ? $guard->validate($credentials)
            : $this->confirmUsingCustomCallback($user, $credentials);
    }

    /**
     * Confirm the user's password using a custom callback.
     */
    protected function confirmUsingCustomCallback(
        WebAuthenticatable $user,
        array $credentials,
    ): bool {
        return call_user_func(
            Cave::$confirmPasswordsUsingCallback,
            $user,
            $credentials,
        );
    }
}
