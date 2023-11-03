<?php

namespace Qruto\Cave;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Qruto\Cave\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Qruto\Cave\Contracts\FailedPasswordConfirmationResponse as FailedPasswordConfirmationResponseContract;
use Qruto\Cave\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Qruto\Cave\Contracts\FailedPasswordResetResponse as FailedPasswordResetResponseContract;
use Qruto\Cave\Contracts\FailedTwoFactorLoginResponse as FailedTwoFactorLoginResponseContract;
use Qruto\Cave\Contracts\LockoutResponse as LockoutResponseContract;
use Qruto\Cave\Contracts\LoginResponse as LoginResponseContract;
use Qruto\Cave\Contracts\LogoutResponse as LogoutResponseContract;
use Qruto\Cave\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Qruto\Cave\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Qruto\Cave\Contracts\PasswordUpdateResponse as PasswordUpdateResponseContract;
use Qruto\Cave\Contracts\ProfileInformationUpdatedResponse as ProfileInformationUpdatedResponseContract;
use Qruto\Cave\Contracts\RecoveryCodesGeneratedResponse as RecoveryCodesGeneratedResponseContract;
use Qruto\Cave\Contracts\RegisterResponse as RegisterResponseContract;
use Qruto\Cave\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;
use Qruto\Cave\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Qruto\Cave\Contracts\TwoFactorConfirmedResponse as TwoFactorConfirmedResponseContract;
use Qruto\Cave\Contracts\TwoFactorDisabledResponse as TwoFactorDisabledResponseContract;
use Qruto\Cave\Contracts\TwoFactorEnabledResponse as TwoFactorEnabledResponseContract;
use Qruto\Cave\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Qruto\Cave\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Qruto\Cave\Http\Responses\EmailVerificationNotificationSentResponse;
use Qruto\Cave\Http\Responses\FailedPasswordConfirmationResponse;
use Qruto\Cave\Http\Responses\FailedPasswordResetLinkRequestResponse;
use Qruto\Cave\Http\Responses\FailedPasswordResetResponse;
use Qruto\Cave\Http\Responses\FailedTwoFactorLoginResponse;
use Qruto\Cave\Http\Responses\LockoutResponse;
use Qruto\Cave\Http\Responses\LoginResponse;
use Qruto\Cave\Http\Responses\LogoutResponse;
use Qruto\Cave\Http\Responses\PasswordConfirmedResponse;
use Qruto\Cave\Http\Responses\PasswordResetResponse;
use Qruto\Cave\Http\Responses\PasswordUpdateResponse;
use Qruto\Cave\Http\Responses\ProfileInformationUpdatedResponse;
use Qruto\Cave\Http\Responses\RecoveryCodesGeneratedResponse;
use Qruto\Cave\Http\Responses\RegisterResponse;
use Qruto\Cave\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;
use Qruto\Cave\Http\Responses\TwoFactorConfirmedResponse;
use Qruto\Cave\Http\Responses\TwoFactorDisabledResponse;
use Qruto\Cave\Http\Responses\TwoFactorEnabledResponse;
use Qruto\Cave\Http\Responses\TwoFactorLoginResponse;
use Qruto\Cave\Http\Responses\VerifyEmailResponse;
use PragmaRX\Google2FA\Google2FA;

class CaveServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fortify.php', 'fortify');

        $this->registerResponseBindings();

        $this->app->singleton(TwoFactorAuthenticationProviderContract::class, function ($app) {
            return new TwoFactorAuthenticationProvider(
                $app->make(Google2FA::class),
                $app->make(Repository::class)
            );
        });

        $this->app->bind(StatefulGuard::class, function () {
            return Auth::guard(config('fortify.guard', null));
        });
    }

    /**
     * Register the response bindings.
     *
     * @return void
     */
    protected function registerResponseBindings()
    {
        $this->app->singleton(FailedPasswordConfirmationResponseContract::class, FailedPasswordConfirmationResponse::class);
        $this->app->singleton(FailedPasswordResetLinkRequestResponseContract::class, FailedPasswordResetLinkRequestResponse::class);
        $this->app->singleton(FailedPasswordResetResponseContract::class, FailedPasswordResetResponse::class);
        $this->app->singleton(FailedTwoFactorLoginResponseContract::class, FailedTwoFactorLoginResponse::class);
        $this->app->singleton(LockoutResponseContract::class, LockoutResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->singleton(PasswordConfirmedResponseContract::class, PasswordConfirmedResponse::class);
        $this->app->singleton(PasswordResetResponseContract::class, PasswordResetResponse::class);
        $this->app->singleton(PasswordUpdateResponseContract::class, PasswordUpdateResponse::class);
        $this->app->singleton(ProfileInformationUpdatedResponseContract::class, ProfileInformationUpdatedResponse::class);
        $this->app->singleton(RecoveryCodesGeneratedResponseContract::class, RecoveryCodesGeneratedResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(EmailVerificationNotificationSentResponseContract::class, EmailVerificationNotificationSentResponse::class);
        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponseContract::class, SuccessfulPasswordResetLinkRequestResponse::class);
        $this->app->singleton(TwoFactorConfirmedResponseContract::class, TwoFactorConfirmedResponse::class);
        $this->app->singleton(TwoFactorDisabledResponseContract::class, TwoFactorDisabledResponse::class);
        $this->app->singleton(TwoFactorEnabledResponseContract::class, TwoFactorEnabledResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePublishing();
        $this->configureRoutes();
    }

    /**
     * Configure the publishable resources offered by the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/fortify.php' => config_path('fortify.php'),
            ], 'fortify-config');

            $this->publishes([
                __DIR__.'/../stubs/CreateNewUser.php' => app_path('Actions/Fortify/CreateNewUser.php'),
                __DIR__.'/../stubs/FortifyServiceProvider.php' => app_path('Providers/FortifyServiceProvider.php'),
                __DIR__.'/../stubs/PasswordValidationRules.php' => app_path('Actions/Fortify/PasswordValidationRules.php'),
                __DIR__.'/../stubs/ResetUserPassword.php' => app_path('Actions/Fortify/ResetUserPassword.php'),
                __DIR__.'/../stubs/UpdateUserProfileInformation.php' => app_path('Actions/Fortify/UpdateUserProfileInformation.php'),
                __DIR__.'/../stubs/UpdateUserPassword.php' => app_path('Actions/Fortify/UpdateUserPassword.php'),
            ], 'fortify-support');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'fortify-migrations');
        }
    }

    /**
     * Configure the routes offered by the application.
     *
     * @return void
     */
    protected function configureRoutes()
    {
        if (Fortify::$registersRoutes) {
            Route::group([
                'namespace' => 'Qruto\Cave\Http\Controllers',
                'domain' => config('fortify.domain', null),
                'prefix' => config('fortify.prefix'),
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
            });
        }
    }
}
