<?php

use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Qruto\Cave\Contracts\CreatesNewUsers;

uses(RefreshDatabase::class);

it('generates options for the new user', function () {
    config()->set('auth.providers.users.model', User::class);

    $this->instance(CreatesNewUsers::class, new class implements CreatesNewUsers {
        public function create(array $input, AuthUser $user): AuthUser
        {
            return $user->fill(['name' => $input['name']]);
        }
    });

    $response = $this->post('auth/options', ['email' => 'rick@unity.io', 'name' => 'Rick Sanchez']);

    expect(User::where('email', 'rick@unity.io')->exists())->toBeTrue();
});
