<?php

namespace Qruto\Cave\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Qruto\Cave\Models\Passkey;

/**
 * @template TModel of \App\Models\User
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class PasskeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Passkey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word,
            'counter' => 0,
            'credential_id' => function (array $data) {
                return Base64UrlSafe::encodeUnpadded($data['user_id']);
            },
            'credential_public_key' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
            'transports' => [],
            'attestation_type' => 'none',
            'attestation_trust_path' => new \Webauthn\TrustPath\EmptyTrustPath,
            'attestation_aaguid' => '00000000-0000-0000-0000-000000000000',
            'last_used_at' => now(),
        ];
    }
}
