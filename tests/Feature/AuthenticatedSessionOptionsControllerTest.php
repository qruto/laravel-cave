<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Qruto\Cave\Contracts\CreatesNewUsers;
use Qruto\Cave\Models\User as AuthUser;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Exception\WebauthnException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('auth.providers.users.model', User::class);
    mockCreatesNewUsers();
});

function mockCreatesNewUsers(): void
{
    app()->instance(CreatesNewUsers::class,
        new class implements CreatesNewUsers
        {
            public function create(array $input, AuthUser $user): AuthUser
            {
                return $user->fill(['name' => $input['name']]);
            }
        });
}

it('creates a new user on auth request', function () {
    $this->post('auth/options',
        ['email' => 'rick@unity.io', 'name' => 'Rick Sanchez']);

    expect(User::where('email', 'rick@unity.io')->exists())->toBeTrue();
});

it('generates options for the new user', function () {
    $this->post(
        'auth/options',
        ['email' => 'rick@unity.io', 'name' => 'Rick Sanchez']
    )->assertJson([
        'rp' => [
            'name' => 'Laravel',
            'id' => 'localhost',
        ],
        'user' => [
            'name' => 'rick@unity.io',
            'displayName' => 'rick@unity.io',
        ],
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],
            ['type' => 'public-key', 'alg' => -46],
            ['type' => 'public-key', 'alg' => -35],
            ['type' => 'public-key', 'alg' => -36],
            ['type' => 'public-key', 'alg' => -257],
            ['type' => 'public-key', 'alg' => -258],
            ['type' => 'public-key', 'alg' => -259],
            ['type' => 'public-key', 'alg' => -37],
            ['type' => 'public-key', 'alg' => -38],
            ['type' => 'public-key', 'alg' => -39],
            ['type' => 'public-key', 'alg' => -260],
            ['type' => 'public-key', 'alg' => -261],
        ],
        'authenticatorSelection' => [
            'requireResidentKey' => false,
            'userVerification' => 'preferred',
            'residentKey' => 'preferred',
        ],
        'attestation' => 'none',
    ]);
});

it('generates options with required user verification', function () {
    config()->set('cave.user_verification', 'required');

    $response = $this->post('auth/options',
        ['email' => 'rick@unity.io', 'name' => 'Rick Sanchez']);

    $response->assertJson([
        'authenticatorSelection' => [
            'userVerification' => 'required',
        ],
    ]);
});

it('generates options without resident key', function () {
    config()->set('cave.resident_key', 'discouraged');

    $this->post(
        'auth/options',
        ['email' => 'rick@unity.io', 'name' => 'Rick Sanchez']
    )->assertJson([
        'authenticatorSelection' => [
            'requireResidentKey' => false,
            'residentKey' => 'discouraged',
            'userVerification' => 'preferred',
        ],
    ]);
});

it('throws an error if resident key is required but user verification is not',
    function () {
        config()->set('cave.user_verification', 'discouraged');
        config()->set('cave.resident_key', 'required');

        $this->expectException(WebauthnException::class);
        $this->expectExceptionMessage('Resident key cannot be required if user verification is not');

        $this->app[AuthenticatorSelectionCriteria::class];
    });

it('generates assertion options with resident key', function () {
    config()->set('cave.resident_key', 'required');

    //    Factory::guessFactoryNamesUsing(function (string $modelName) {
    //        return 'Database\\Factories\\'.class_basename($modelName).'Factory';
    //    });

    $user = User::factory()->hasPasskeys()->create();

    ray($user);

    $this->post(
        'auth/options',
        ['email' => 'rick@unity.io', 'name' => 'Rick Sanchez']
    )->assertJson([
        'authenticatorSelection' => [
            'requireResidentKey' => true,
            'residentKey' => 'required',
            'userVerification' => 'preferred',
        ],
    ]);
});

todo('generates options with attestation');
todo('generates options with pubKeyCredParams');
todo('generates options with extensions');
