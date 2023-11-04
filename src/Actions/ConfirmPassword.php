<?php

namespace Qruto\Cave\Actions;

use Illuminate\Contracts\Auth\StatefulGuard;
use Qruto\Cave\Cave;

class ConfirmPassword
{
    /**
     * Confirm that the given password is valid for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @param  mixed  $user
     * @param  string|null  $password
     * @return bool
     */
    public function __invoke(StatefulGuard $guard, $user, ?string $password = null)
    {
        $username = Cave::username();

        return is_null(Cave::$confirmPasswordsUsingCallback) ? $guard->validate([
            $username => $user->{$username},
            'password' => $password,
        ]) : $this->confirmPasswordUsingCustomCallback($user, $password);
    }

    /**
     * Confirm the user's password using a custom callback.
     *
     * @param  mixed  $user
     * @param  string|null  $password
     * @return bool
     */
    protected function confirmPasswordUsingCustomCallback($user, ?string $password = null)
    {
        return call_user_func(
            Cave::$confirmPasswordsUsingCallback,
            $user,
            $password
        );
    }
}
