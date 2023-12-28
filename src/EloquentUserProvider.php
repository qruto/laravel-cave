<?php

namespace Qruto\Cave;

use Illuminate\Auth\EloquentUserProvider as BaseEloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Qruto\Cave\Authenticators\Assertion;
use Qruto\Cave\Models\Passkey;
use Throwable;
use Webauthn\Util\Base64;

class EloquentUserProvider extends BaseEloquentUserProvider
{
    public function __construct(
        private Assertion $assertion,
        Hasher $hasher,
        $model
    ) {
        parent::__construct($hasher, $model);
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (! $this->isValidCredentials($credentials)) {
            return null;
        }

        $webauthnKey = Passkey::where('credential_id',
            Base64UrlSafe::encode(Base64::decode($credentials['id'])))
            ->orWhere('credential_id',
                Base64UrlSafe::encodeUnpadded(Base64::decode($credentials['id'])))
            ->first();

        if (! $webauthnKey) {
            return null;
        }

        return $this->retrieveById($webauthnKey->user_id);
    }

    /**
     * Check if the credentials are for a public key signed challenge.
     */
    protected function isValidCredentials(array $credentials): bool
    {
        return isset($credentials['id'], $credentials['rawId'], $credentials['type'], $credentials['response']);
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(
        Authenticatable $user,
        array $credentials
    ): bool {
        if ($this->isValidCredentials($credentials) && session()->has($this->assertion::OPTIONS_SESSION_KEY)) {
            try {
                $this->assertion->verify($credentials,
                    session($this->assertion::OPTIONS_SESSION_KEY));
            } catch (Throwable $th) {
                return false;
            }

            return true;
        }

        return false;
    }
}
