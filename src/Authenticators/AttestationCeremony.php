<?php

namespace Qruto\Cave\Authenticators;

use Qruto\Cave\Contracts\WebAuthenticatable;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

// TODO: leave just `Attestation` as the name of the interface ?
interface AttestationCeremony
{
    public const OPTIONS_SESSION_KEY = 'public_key_credential_creation_options';

    public function newOptions(WebAuthenticatable $user = null
    ): PublicKeyCredentialCreationOptions;

    public function verify(
        array $credential,
        PublicKeyCredentialCreationOptions $options
    ): PublicKeyCredentialSource;
}
