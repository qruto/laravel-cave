<?php

namespace Qruto\Cave;

use Qruto\Cave\Contracts\AuthViewResponse;
use Qruto\Cave\Contracts\ConfirmPasswordViewResponse;
use Qruto\Cave\Contracts\CreatesNewUsers;
use Qruto\Cave\Contracts\RegisterViewResponse;
use Qruto\Cave\Contracts\RequestPasswordResetLinkViewResponse;
use Qruto\Cave\Contracts\ResetsUserPasswords;
use Qruto\Cave\Contracts\UpdatesUserPasswords;
use Qruto\Cave\Contracts\UpdatesUserProfileInformation;
use Qruto\Cave\Contracts\VerifyEmailViewResponse;
use Qruto\Cave\Http\Responses\SimpleViewResponse;

class Cave
{
    /**
     * The callback that is responsible for building the authentication pipeline array, if applicable.
     *
     * @var callable|null
     */
    public static $authenticateThroughCallback;

    /**
     * The callback that is responsible for validating authentication credentials, if applicable.
     *
     * @var callable|null
     */
    public static $authenticateUsingCallback;

    /**
     * The callback that is responsible for confirming user passwords.
     *
     * @var callable|null
     */
    public static $confirmPasswordsUsingCallback;

    /**
     * Indicates if Fortify routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    const PASSWORD_UPDATED = 'password-updated';

    const PROFILE_INFORMATION_UPDATED = 'profile-information-updated';

    const RECOVERY_CODES_GENERATED = 'recovery-codes-generated';

    const TWO_FACTOR_AUTHENTICATION_CONFIRMED = 'two-factor-authentication-confirmed';

    const TWO_FACTOR_AUTHENTICATION_DISABLED = 'two-factor-authentication-disabled';

    const TWO_FACTOR_AUTHENTICATION_ENABLED = 'two-factor-authentication-enabled';

    const VERIFICATION_LINK_SENT = 'verification-link-sent';

    /**
     * Get the username used for authentication.
     *
     * @return string
     */
    public static function username()
    {
        return config('cave.username', 'email');
    }

    /**
     * Get the name of the email address request variable / field.
     *
     * @return string
     */
    public static function email()
    {
        return config('cave.email', 'email');
    }

    /**
     * Get a completion redirect path for a specific feature.
     *
     * @return string
     */
    public static function redirects(string $redirect, $default = null)
    {
        return config('cave.redirects.'.$redirect) ?? $default ?? config('cave.home');
    }

    /**
     * Register the views for Fortify using conventional names under the given namespace.
     *
     * @return void
     */
    public static function viewNamespace(string $namespace)
    {
        static::viewPrefix($namespace.'::');
    }

    /**
     * Register the views for Fortify using conventional names under the given prefix.
     *
     * @return void
     */
    public static function viewPrefix(string $prefix)
    {
        static::authView($prefix.'login');
        static::requestPasswordResetLinkView($prefix.'forgot-password');
        static::verifyEmailView($prefix.'verify-email');
        static::confirmPasswordView($prefix.'confirm-password');
    }

    /**
     * Specify which view should be used as the login view.
     *
     * @param  callable|string  $view
     * @return void
     */
    public static function authView($view)
    {
        app()->singleton(AuthViewResponse::class, function () use ($view) {
            return new SimpleViewResponse($view);
        });
    }

    /**
     * Specify which view should be used as the email verification prompt.
     *
     * @param  callable|string  $view
     * @return void
     */
    public static function verifyEmailView($view)
    {
        app()->singleton(VerifyEmailViewResponse::class,
            function () use ($view) {
                return new SimpleViewResponse($view);
            });
    }

    /**
     * Specify which view should be used as the password confirmation prompt.
     *
     * @param  callable|string  $view
     * @return void
     */
    public static function confirmPasswordView($view)
    {
        app()->singleton(ConfirmPasswordViewResponse::class,
            function () use ($view) {
                return new SimpleViewResponse($view);
            });
    }

    /**
     * Specify which view should be used as the request password reset link view.
     *
     * @param  callable|string  $view
     * @return void
     */
    public static function requestPasswordResetLinkView($view)
    {
        app()->singleton(RequestPasswordResetLinkViewResponse::class,
            function () use ($view) {
                return new SimpleViewResponse($view);
            });
    }

    /**
     * Register a callback that is responsible for building the authentication pipeline array.
     *
     * @return void
     */
    public static function loginThrough(callable $callback)
    {
        static::authenticateThrough($callback);
    }

    /**
     * Register a callback that is responsible for building the authentication pipeline array.
     *
     * @return void
     */
    public static function authenticateThrough(callable $callback)
    {
        static::$authenticateThroughCallback = $callback;
    }

    /**
     * Register a callback that is responsible for validating incoming authentication credentials.
     *
     * @return void
     */
    public static function authenticateUsing(callable $callback)
    {
        static::$authenticateUsingCallback = $callback;
    }

    /**
     * Register a callback that is responsible for confirming existing user passwords as valid.
     *
     * @return void
     */
    public static function confirmPasswordsUsing(callable $callback)
    {
        static::$confirmPasswordsUsingCallback = $callback;
    }

    /**
     * Register a class / callback that should be used to create new users.
     *
     * @return void
     */
    public static function createUsersUsing(string $callback)
    {
        app()->singleton(CreatesNewUsers::class, $callback);
    }

    /**
     * Register a class / callback that should be used to update user profile information.
     *
     * @return void
     */
    public static function updateUserProfileInformationUsing(string $callback)
    {
        app()->singleton(UpdatesUserProfileInformation::class, $callback);
    }

    /**
     * Register a class / callback that should be used to update user passwords.
     *
     * @return void
     */
    public static function updateUserPasswordsUsing(string $callback)
    {
        app()->singleton(UpdatesUserPasswords::class, $callback);
    }

    /**
     * Register a class / callback that should be used to reset user passwords.
     *
     * @return void
     */
    public static function resetUserPasswordsUsing(string $callback)
    {
        app()->singleton(ResetsUserPasswords::class, $callback);
    }

    /**
     * Determine if Fortify is confirming two factor authentication configurations.
     *
     * @return bool
     */
    public static function confirmsTwoFactorAuthentication()
    {
        return Features::enabled(Features::twoFactorAuthentication()) &&
            Features::optionEnabled(Features::twoFactorAuthentication(),
                'confirm');
    }

    /**
     * Configure Fortify to not register its routes.
     *
     * @return static
     */
    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static;
    }
}
