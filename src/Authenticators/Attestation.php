<?php

namespace Qruto\Cave\Authenticators;

use Qruto\Cave\Authenticator\InvalidAuthenticatorResponseException;
use Qruto\Cave\Cave;
use Qruto\Cave\Challenge;
use Qruto\Cave\Contracts\WebAuthenticatable;
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
        return collect(config('cave.public_key_credential_algorithms'))->map(
            fn ($algorithm) => PublicKeyCredentialParameters::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm
            )
        )->toArray();
    }

    public function newOptions(?WebAuthenticatable $user): PublicKeyCredentialCreationOptions
    {
        return PublicKeyCredentialCreationOptions::create(
            $this->rpEntity,
            // TODO: abstract
            PublicKeyCredentialUserEntity::create(
                $user->{Cave::username()},
                $user->getAuthIdentifier(),
                $user->{Cave::username()},
                // TODO: create icon
                null,
            ),
            new Challenge(),
            self::supportedParams(),
            $this->selectionCriteria,
            config('cave.attestation_conveyance'),
            timeout: config('cave.timeout'),
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
