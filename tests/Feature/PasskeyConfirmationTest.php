<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Qruto\Cave\Authenticators\AssertionCeremony;
use Qruto\Cave\Cave;
use Qruto\Cave\Contracts\ConfirmPasskeyViewResponse;
use Webauthn\PublicKeyCredentialRequestOptions;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('auth.providers.users.model', User::class);
});

it('returns page view with passkey confirmation', function () {
    $this->mock(ConfirmPasskeyViewResponse::class)
        ->shouldReceive('toResponse')
        ->andReturn(response('check your identity'));

    actingAs(User::factory()->create())
        ->get('/user/confirm-passkey')
        ->assertStatus(200)
        ->assertSeeText('check your identity');
});

it('passes options to the view', function () {
    Cave::confirmPasswordView(
        function (PublicKeyCredentialRequestOptions $options, $request) {
            expect($options)
                ->toBeInstanceOf(PublicKeyCredentialRequestOptions::class)
                ->and($request)
                ->toBeInstanceOf(Request::class);

            return response('check your identity');
        }
    );

    actingAs(User::factory()->create())
        ->get('/user/confirm-passkey')
        ->assertStatus(200)
        ->assertSeeText('check your identity');
});

it('can be confirmed with a valid credentials', function () {
    $user = User::factory()->hasPasskeys()->create();

    $passkey = $user->passkeys()->first();

    $assertion = app(AssertionCeremony::class);

    $options = $assertion->newOptions($user);

    session()->put(AssertionCeremony::OPTIONS_SESSION_KEY, $options);

    $credentials = [
        'id' => base64_encode($passkey->credential_id),
        'rawId' => base64_encode($passkey->credential_id),
        'type' => 'public-key',
        'response' => [
            'authenticatorData' => 'authenticatorData',
            'clientDataJSON' => 'clientDataJSON',
            'signature' => 'signature',
            'userHandle' => 'userHandle',
        ],
    ];

    $this->mock(AssertionCeremony::class)
        ->shouldReceive('verify')
        ->withArgs([$credentials, $options])
        ->andReturn($passkey->publicKeyCredentialSource());

    $response = actingAs($user)
        ->withSession(['url.intended' => 'http://foo.com/bar'])
        ->post(
            '/user/confirm-passkey',
            $credentials
        );

    $response->assertSessionHas('auth.passkey_confirmed_at');
    $response->assertRedirect('http://foo.com/bar');
});
