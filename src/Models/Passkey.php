<?php

namespace Qruto\Cave\Models;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Qruto\Cave\Contracts\WebAuthenticatable;
use Qruto\Cave\Database\Factories\PasskeyFactory;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;

class Passkey extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array<int,string>
     */
    protected $visible = [
        'id',
        'name',
        'transports',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'counter' => 'integer',
        'transports' => 'array',
        'credential_id' => Base64Cast::class,
        'credential_public_key' => Base64Cast::class,
        'attestation_trust_path' => TrustPathCast::class,
        'last_used_at' => 'timestamp',
    ];

    /**
     * Get PublicKeyCredentialSource object from WebauthnKey attributes.
     */
    public static function createFromSource(
        PublicKeyCredentialSource $source,
        WebAuthenticatable $user,
        string $name,
    ) {
        $passkey = new self();

        $passkey->name = $name;
        $passkey->credential_id = $source->publicKeyCredentialId;
        $passkey->credential_public_key = $source->credentialPublicKey;
        $passkey->transports = $source->transports;
        $passkey->counter = $source->counter;
        $passkey->attestation_type = $source->attestationType;
        $passkey->attestation_trust_path = $source->trustPath;
        $passkey->attestation_aaguid = $source->aaguid;
        $passkey->last_used_at = now();

        $passkey->user()->associate($user);

        $passkey->save();

        return $passkey;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(app(StatefulGuard::class)->getProvider()->getModel());
    }

    protected static function newFactory(): PasskeyFactory
    {
        return PasskeyFactory::new();
    }

    public function publicKeyCredentialSource(): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            $this->credential_id,
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $this->transports,
            $this->attestation_type,
            $this->attestation_trust_path,
            Uuid::fromString($this->attestation_aaguid),
            $this->credential_public_key,
            $this->user_id,
            $this->counter
        );
    }
}
