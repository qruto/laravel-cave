<?php

namespace Qruto\Cave\Authenticators;

use Qruto\Cave\Contracts\WebAuthenticatable;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

interface AttestationCeremony
{
    public function newOptions(?WebAuthenticatable $user): PublicKeyCredentialCreationOptions;

    public function verify(array $credential, PublicKeyCredentialCreationOptions $options): PublicKeyCredentialSource;
}
