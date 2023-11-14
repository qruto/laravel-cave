<?php

namespace Qruto\Cave\Authenticators;

use Qruto\Cave\Challenge;
use Illuminate\Contracts\Auth\Authenticatable;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class Attestation implements AttestationCeremony
{
    public function __construct(
        readonly private PublicKeyCredentialRpEntity $rpEntity,
        readonly private AuthenticatorSelectionCriteria $selectionCriteria,
        readonly private AuthenticationExtensionsClientInputs $extensions,
        readonly private PublicKeyCredentialLoader $credentialLoader,
        readonly private AuthenticatorAttestationResponseValidator $validator,
    ) {
    }

    private static function supportedParams(): array
    {
        return collect(config('webauthn.public_key_credential_algorithms'))->map(
            fn ($algorithm) => PublicKeyCredentialParameters::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm
            )
        )->toArray();
    }

    public function newOptions(?Authenticatable $user): PublicKeyCredentialCreationOptions
    {
        return PublicKeyCredentialCreationOptions::create(
            $this->rpEntity,
            PublicKeyCredentialUserEntity::create(
                // TODO: abstract
                $user->email,
                $user->getAuthIdentifier(),
                $user->email,
                // TODO: create icon
                null,
            ),
            new Challenge(),
            self::supportedParams(),
            $this->selectionCriteria,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            timeout: config('webauthn.timeout'),
            // TODO: implement
            //       excludeCredentials: [],
            extensions: $this->extensions,
        );
    }

    public function verify(array $credential, PublicKeyCredentialCreationOptions $options): PublicKeyCredentialSource
    {
        $publicKeyCredential = $this->credentialLoader->load(json_encode($credential));

        $authenticatorAttestationResponse = $publicKeyCredential->response;

        if (! $authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
            throw new InvalidAuthenticatorResponseException();
        }

        return $this->validator->check(
            $authenticatorAttestationResponse,
            $options,
            app('host')
        );
    }
}
