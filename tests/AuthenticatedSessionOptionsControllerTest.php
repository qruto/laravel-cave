<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates options for the new user', function () {

    $response = $this->post('auth/options', ['email' => 'rick@unity.io']);

    expect(User::where('email', 'rick@unity.io')->exists())->toBeTrue();

//    $this->assertDatabaseHas('users', [
//        'id' => $user->id,
//        'current_team_id' => null,
//        'email' => $user->email,
//        'email_verified_at' => null,
//        'verified_at' => null,
//        'remember_token' => null,
//    ]);
});
