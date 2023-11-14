<?php

namespace Qruto\Cave\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;

class Passkey extends Model
{
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
    public static function createFromSource(PublicKeyCredentialSource $source, User $user)
    {
        $authKey = new self();

        $authKey->credential_id = $source->publicKeyCredentialId;
        $authKey->credential_public_key = $source->credentialPublicKey;
        $authKey->transports = $source->transports;
        $authKey->counter = $source->counter;
        $authKey->attestation_type = $source->attestationType;
        $authKey->attestation_trust_path = $source->trustPath;
        $authKey->attestation_aaguid = $source->aaguid;
        $authKey->last_used_at = now();

        $authKey->user()->associate($user);

        $authKey->save();

        return $authKey;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
