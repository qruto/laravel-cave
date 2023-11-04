<?php

namespace App\Auth;

use App\Auth\Authenticator\Assertion;
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
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;
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

use function config;
use function inertia;

class WebAuthnServiceProvider extends ServiceProvider
{
    public function register(): void
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
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            true,
        ));

        $this->app->bind(AuthenticationExtensionsClientInputs::class, fn () => AuthenticationExtensionsClientInputs::createFromArray(config('webauthn.extensions'))
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

        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request): RedirectResponse
            {
                return redirect('/auth');
            }
        });
    }

    public function boot(): void
    {
        Fortify::registerView(fn () => inertia('auth/register'));

        $this->app['auth']->provider('eloquent', fn ($app, array $config) => new EloquentUserProvider(
            $app[Assertion::class],
            $app[Hasher::class],
            $config['model'],
        ));
    }
}
