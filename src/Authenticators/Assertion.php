<?php

namespace Qruto\Cave\Authenticators;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Qruto\Cave\Challenge;
use Qruto\Cave\Contracts\WebAuthenticatable;
use Qruto\Cave\Models\Passkey;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;

class Assertion implements AssertionCeremony
{
    public function __construct(
        readonly private PublicKeyCredentialRpEntity $rpEntity,
        readonly private AuthenticationExtensionsClientInputs $extensions,
        readonly private PublicKeyCredentialLoader $credentialLoader,
        readonly private AuthenticatorAssertionResponseValidator $validator,
    ) {
    }

    public function newOptions(WebAuthenticatable $user = null
    ): PublicKeyCredentialRequestOptions {
        return PublicKeyCredentialRequestOptions::create(
            new Challenge(),
            $this->rpEntity->id,
            ($user && in_array(config('cave.resident_key', null),
                ['discouraged', null]))
                ? $user->passkeys->map
                ->publicKeyCredentialSource()->map
                ->getPublicKeyCredentialDescriptor()->toArray()
                : [],
            config('cave.user_verification', 'preferred'),
            config('cave.timeout'),
            $this->extensions
        );
    }

    public function verify(
        array $credential,
        PublicKeyCredentialRequestOptions $options
    ): PublicKeyCredentialSource {
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

    protected function getCredentialSource(
        PublicKeyCredential $publicKeyCredential
    ) {
        $credentialId = $publicKeyCredential->rawId;

        return Passkey::where(
            fn ($query) => $query->where(
                'credential_id',
                Base64UrlSafe::encode($credentialId)
            )->orWhere(
                'credential_id',
                Base64UrlSafe::encodeUnpadded($credentialId)
            )
        )->firstOrFail()->publicKeyCredentialSource();
    }
}
