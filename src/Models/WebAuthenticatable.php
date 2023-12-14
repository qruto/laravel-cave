<?php

namespace Qruto\Cave\Models;

use BadMethodCallException;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webauthn\PublicKeyCredentialUserEntity;

/** @mixin Model */
trait WebAuthenticatable
{
    use Authenticatable;

    public function getPassword()
    {
        throw new BadMethodCallException('Deprecated. Use `authKeys` relation to get stored credentials.');
    }

    public function newPublicKeyCredentialUserEntity(string $email)
    {
        $entity = self::create([
            'email' => $email,
        ]);

        return PublicKeyCredentialUserEntity::create(
            $entity->email,
            $entity->id,
            $entity->email,
            // TODO: create icon
            null,
        );
    }

    /**
     * Get the webauthn keys associated to this user.
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }
}
