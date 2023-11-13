<?php

namespace App\Auth\Authenticator;

use Illuminate\Contracts\Auth\Authenticatable;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

interface AttestationCeremony
{
    public function newOptions(?Authenticatable $user): PublicKeyCredentialCreationOptions;

    public function verify(array $credential, PublicKeyCredentialCreationOptions $options): PublicKeyCredentialSource;
}
