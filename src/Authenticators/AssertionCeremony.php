<?php

namespace Qruto\Cave\Authenticators;

use Qruto\Cave\Contracts\WebAuthenticatable;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

interface AssertionCeremony
{
    // TODO: remove SESSION from name
    public const OPTIONS_SESSION_KEY = 'public_key_credential_request_options';

    public function newOptions(WebAuthenticatable $user = null
    ): PublicKeyCredentialRequestOptions;

    public function verify(
        array $credential,
        PublicKeyCredentialRequestOptions $options
    ): PublicKeyCredentialSource;
}
