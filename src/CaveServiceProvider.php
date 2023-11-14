<?php

namespace Qruto\Cave;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Qruto\Cave\Authenticators\Assertion;
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
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;

class CaveServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package) : void
    {
        $package->name('cave')
            ->hasConfigFile()
            ->hasRoute('web')
            ->hasMigration('create_passkeys_table')
            ->hasMigration('prepare_users_table_to_use_passkeys')
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('qruto/laravel-cave');
            });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function packageRegistered()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cave-system.php', 'cave');

        $this->registerWebAuthnEntities();

        $this->registerResponseBindings();

        $this->app->bind(StatefulGuard::class, function () {
            return Auth::guard(config('cave.guard', null));
        });
    }

    protected function registerWebAuthnEntities()
    {
        $this->app->instance('host', parse_url(config('app.url'), PHP_URL_HOST));

        $this->app->bind(PublicKeyCredentialRpEntity::class, fn () => new PublicKeyCredentialRpEntity(
            config('app.name'),
            $this->app['host'],
            // TODO: Add icon
            null,
        ));

        $this->app->bind(AuthenticatorSelectionCriteria::class, fn () => new AuthenticatorSelectionCriteria(
            AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
            config('cave.user_verification', 'preferred'),
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            true,
        ));

        $this->app->bind(AuthenticationExtensionsClientInputs::class, fn () => AuthenticationExtensionsClientInputs::createFromArray(config('cave.extensions'))
        );

        $this->app->bind(AttestationStatementSupportManager::class, fn () => tap(new AttestationStatementSupportManager())->add(NoneAttestationStatementSupport::create()));

        $this->app->bind(PublicKeyCredentialLoader::class, fn () => new PublicKeyCredentialLoader(
            new AttestationObjectLoader($this->app[AttestationStatementSupportManager::class]),
        ));

        $this->app->bind(AuthenticatorAttestationResponseValidator::class, fn () => new AuthenticatorAttestationResponseValidator(
            $this->app[AttestationStatementSupportManager::class],
            null,
            null,
            ExtensionOutputCheckerHandler::create(),
        ));

        $this->app->bind(Manager::class, fn () => tap(Manager::create())->add(
            ES256::create(),
            ES256K::create(),
            ES384::create(),
            ES512::create(),
            RS256::create(),
            RS384::create(),
            RS512::create(),
            PS256::create(),
            PS384::create(),
            PS512::create(),
            Ed256::create(),
            Ed512::create(),
        ));

        $this->app->bind(AuthenticatorAssertionResponseValidator::class, fn () => new AuthenticatorAssertionResponseValidator(
            null,
            null,
            ExtensionOutputCheckerHandler::create(),
            $this->app[Manager::class],
        ));
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
    public function packageBooted()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/CreateNewUser.php' => app_path('Actions/Cave/CreateNewUser.php'),
                __DIR__.'/../stubs/CaveServiceProvider.php' => app_path('Providers/CaveServiceProvider.php'),
                __DIR__.'/../stubs/PasswordValidationRules.php' => app_path('Actions/Cave/PasswordValidationRules.php'),
                __DIR__.'/../stubs/ResetUserPassword.php' => app_path('Actions/Cave/ResetUserPassword.php'),
                __DIR__.'/../stubs/UpdateUserProfileInformation.php' => app_path('Actions/Cave/UpdateUserProfileInformation.php'),
                __DIR__.'/../stubs/UpdateUserPassword.php' => app_path('Actions/Cave/UpdateUserPassword.php'),
            ], 'cave-support');

        }

        $this->app['auth']->provider('eloquent', fn ($app, array $config) => new EloquentUserProvider(
            $app[Assertion::class],
            $app[Hasher::class],
            $config['model'],
        ));
    }
}
