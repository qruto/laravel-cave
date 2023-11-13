<?php

namespace App\Auth\Authenticator;

use App\Auth\Challenge;
use App\Auth\Models\Passkey;
use Illuminate\Contracts\Auth\Authenticatable;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;

class Assertion
{
    public function __construct(
        readonly private PublicKeyCredentialRpEntity $rpEntity,
        readonly private AuthenticationExtensionsClientInputs $extensions,
        readonly private PublicKeyCredentialLoader $credentialLoader,
        readonly private AuthenticatorAssertionResponseValidator $validator,
    ) {
    }

    public function newOptions(Authenticatable $user = null): PublicKeyCredentialOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            new Challenge(),
            $this->rpEntity->id,
            $user ? $user->passkeys->map->publicKeyCredentialSource()->map->getPublicKeyCredentialDescriptor()->toArray() : [],
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            config('webauthn.timeout'),
            $this->extensions
        );
    }

    public function verify(array $credential, PublicKeyCredentialRequestOptions $options): PublicKeyCredentialSource
    {
        $publicKeyCredential = $this->credentialLoader->loadArray($credential);

        $authenticatorAssertionResponse = $publicKeyCredential->response;

        if (! $authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            throw new InvalidAuthenticatorResponseException();
        }

        return $this->validator->check(
            $this->getCredentialSource($publicKeyCredential),
            $authenticatorAssertionResponse,
            $options,
            app('host'),
            $authenticatorAssertionResponse->userHandle,
        );
    }

    protected function getCredentialSource(PublicKeyCredential $publicKeyCredential)
    {
        $credentialId = $publicKeyCredential->rawId;

        return Passkey::where(fn ($query) => $query->where('credential_id', Base64UrlSafe::encode($credentialId))
            ->orWhere('credential_id', Base64UrlSafe::encodeUnpadded($credentialId))
        )
            ->firstOrFail()
            ->publicKeyCredentialSource();
    }
}
