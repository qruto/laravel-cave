<?php

namespace Qruto\Cave\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
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
            'credential_id' => random_bytes(32),
            'credential_public_key' => random_bytes(32),
            'transports' => ['internal'],
            'attestation_type' => 'none',
            'attestation_trust_path' => new \Webauthn\TrustPath\EmptyTrustPath,
            'attestation_aaguid' => '00000000-0000-0000-0000-000000000000',
            'last_used_at' => now(),
        ];
    }
}
