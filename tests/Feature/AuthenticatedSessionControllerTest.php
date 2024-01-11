<?php

namespace Qruto\Cave\Tests;

use App\Models\User;
use Hamcrest\Core\IsEqual;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Mockery\CompositeExpectation;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Qruto\Cave\Authenticators\AssertionCeremony;
use Qruto\Cave\Authenticators\AttestationCeremony;
use Qruto\Cave\AuthVerificationRateLimiter;
use Qruto\Cave\Ceremony;
use Qruto\Cave\Challenge;
use Qruto\Cave\Contracts\AuthViewResponse;
use Qruto\Cave\Http\Requests\AuthVerifyRequest;
use Qruto\Cave\Models\Passkey;
use ReflectionClass;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorData;
use Webauthn\CollectedClientData;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialLoader;

use function beforeEach;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(fn () => app('config')->set([
    'auth.providers.users.model' => User::class,
]));

test('the auth view is returned', function () {
    $this->mock(AuthViewResponse::class)
        ->shouldReceive('toResponse')
        ->andReturn(response('hello world'));

    $response = $this->get('/auth');

    $response->assertStatus(200);
    $response->assertSeeText('hello world');
});

test('the user attestation verification', function () {
    $user = \App\Models\User::factory()->create([
        'passkey_verified_at' => null,
    ]);

    $attestation = app(AttestationCeremony::class);

    $options = $attestation->newOptions($user);

    $credentialId = random_bytes(32);

    $authenticatorAttestationResponse = mock(AuthenticatorAttestationResponse::class);

    $publicKeyCredential = PublicKeyCredential::create(
        $credentialId,
        'public-key',
        $credentialId,
        $authenticatorAttestationResponse
    );

    $this->mock(PublicKeyCredentialLoader::class)
        ->shouldReceive('loadArray')
        ->andReturn($publicKeyCredential);

    $publicKeyCredentialSource = Passkey::factory()->make([
        'user_id' => $user->id,
        'credential_id' => $credentialId,
    ])->publicKeyCredentialSource();

    $this->mock(AuthenticatorAttestationResponseValidator::class)
        ->shouldReceive('check')
        ->withArgs([$publicKeyCredential->response, $options, app('host')])
        ->andReturn($publicKeyCredentialSource);

    session()->put(
        AttestationCeremony::OPTIONS_SESSION_KEY,
        $options
    );

    $response = $this->post('/auth', [
        'id' => \base64_encode($credentialId),
        'rawId' => \base64_encode($credentialId),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => 'clientDataJSON',
            'attestationObject' => 'attestationObject',
        ],
    ], headers: [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
    ]);

    $response->assertSessionMissing(AttestationCeremony::OPTIONS_SESSION_KEY);

    $this->assertDatabaseHas('passkeys', [
        'user_id' => $user->id,
        'credential_id' => Base64UrlSafe::encode($credentialId),
        'name' => 'OS X (Safari)',
    ]);

    $response->assertRedirect('/home');

    $this->assertAuthenticatedAs($user);
});

test('the user assertion verification', function () {
    [$response, $user, $credentialId] = prepareAssertion();

    $response->assertSessionMissing(AssertionCeremony::OPTIONS_SESSION_KEY);

    $this->assertDatabaseHas('passkeys', [
        'user_id' => $user->id,
        'credential_id' => Base64UrlSafe::encode($credentialId),
    ]);

    $response->assertRedirect('/home');

    $this->assertAuthenticatedAs($user);
});

test('the user assertion verification fails with invalid credentials',
    function () {
        [$response, $user, $credentialId] = prepareAssertion(
            fn (
                CompositeExpectation $e
            ) => $e->andThrow(AuthenticatorResponseVerificationException::create('Invalid credentials'))
        );

        $response->assertSessionHas(AssertionCeremony::OPTIONS_SESSION_KEY);

        $this->assertDatabaseHas('passkeys', [
            'user_id' => (string) $user->getAuthIdentifier(),
            'credential_id' => Base64UrlSafe::encode($credentialId),
        ]);

        $response->assertRedirect('/auth');

        $this->assertGuest();
    }
);

test('the assertion verification validation fails', function () {
    [$response] = prepareAssertion(data: fn (array $data) => array_filter($data,
        fn ($key) => $key !== 'rawId', ARRAY_FILTER_USE_KEY));

    $response->assertSessionHas(AssertionCeremony::OPTIONS_SESSION_KEY);

    $response->assertSessionHasErrors('rawId');
});

test('user can authenticate', function () {
    mockCreatesNewUsers();

    $response = $this->post(route('auth.options'), [
        'email' => 'rick@c137.planet',
    ]);

    $response->assertStatus(Response::HTTP_CREATED);

    $credentialId = random_bytes(32);

    $authenticatorAttestationResponse = mock(AuthenticatorAttestationResponse::class);

    $publicKeyCredential = PublicKeyCredential::create(
        $credentialId,
        'public-key',
        $credentialId,
        $authenticatorAttestationResponse
    );

    $this->mock(PublicKeyCredentialLoader::class)
        ->shouldReceive('loadArray')
        ->andReturn($publicKeyCredential);

    $publicKeyCredentialSource = Passkey::factory()->make([
        'user_id' => 1,
        'credential_id' => $credentialId,
    ])->publicKeyCredentialSource();

    $this->mock(AuthenticatorAttestationResponseValidator::class)
        ->shouldReceive('check')
        ->withArgs([
            $publicKeyCredential->response,
            session()->get(AttestationCeremony::OPTIONS_SESSION_KEY),
            app('host'),
        ])
        ->andReturn($publicKeyCredentialSource);

    $response = $this->post('/auth', [
        'id' => \base64_encode($credentialId),
        'rawId' => \base64_encode($credentialId),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => 'clientDataJSON',
            'attestationObject' => 'attestationObject',
        ],
    ], headers: [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
    ]);

    $response->assertSessionMissing(AttestationCeremony::OPTIONS_SESSION_KEY);

    $this->assertDatabaseHas('passkeys', [
        'user_id' => 1,
        'credential_id' => Base64UrlSafe::encode($credentialId),
        'name' => 'OS X (Safari)',
    ]);

    $response->assertRedirect('/home');

    $this->assertAuthenticatedAs(User::first());
});

//    public function test_login_attempts_are_throttled()
//    {
//        $this->mock(LoginRateLimiter::class, function ($mock) {
//            $mock->shouldReceive('tooManyAttempts')->andReturn(true);
//            $mock->shouldReceive('availableIn')->andReturn(10);
//        });
//
//        $response = $this->postJson('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'secret',
//        ]);
//
//        $response->assertStatus(429);
//        $response->assertJsonValidationErrors(['email']);
//    }

test('auth attestation attempts are throttled', function () {
    $this->mock(RateLimiter::class, function ($mock) {
        $mock->shouldReceive('tooManyAttempts')
            ->withArgs(['attest:127.0.0.1', 5])
            ->andReturn(true);
        $mock->shouldReceive('availableIn')
            ->withArgs(['attest:127.0.0.1'])
            ->andReturn(10);
    });

    session()->put(AttestationCeremony::OPTIONS_SESSION_KEY, []);

    $response = $this->postJson(route('auth'), [
        'id' => 'id',
        'rawId' => 'rawId',
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => 'clientDataJSON',
            'attestationObject' => 'attestationObject',
        ],
    ]);

    $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    $response->assertContent(trans('auth.throttle', [
        'seconds' => 10,
        'minutes' => 1,
    ]));
});

test('auth assertion attempts are throttled', function () {
    $this->mock(RateLimiter::class, function ($mock) {
        $mock->shouldReceive('tooManyAttempts')
            ->withArgs(['auth:id|127.0.0.1', 5])
            ->andReturn(true);

        $mock->shouldReceive('availableIn')
            ->withArgs(['auth:id|127.0.0.1'])
            ->andReturn(10);
    });

    session()->put(AssertionCeremony::OPTIONS_SESSION_KEY, []);

    $response = $this->postJson(route('auth'), [
        'id' => 'id',
        'rawId' => 'rawId',
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => 'clientDataJSON',
            'authenticatorData' => 'authenticatorData',
            'signature' => 'signature',
            'userHandle' => 'userHandle',
        ],
    ]);

    $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
});

//$usernames = [
//    'lowercase special characters' => ['ⓣⓔⓢⓣ@ⓛⓐⓡⓐⓥⓔⓛ.ⓒⓞⓜ', 'test@laravel.com'],
//    'uppercase special characters' => ['ⓉⒺⓈⓉ@ⓁⒶⓇⒶⓋⒺⓁ.ⒸⓄⓂ', 'test@laravel.com'],
//    'special character numbers' => [
//        'test⑩⓸③@laravel.com', 'test1043@laravel.com',
//    ],
//    'default email' => ['test@laravel.com', 'test@laravel.com'],
//];

test('cant bypass throttle with special characters',
    function () {
        $authRateLimiter = new AuthVerificationRateLimiter(mock(RateLimiter::class));

        $reflection = new ReflectionClass($authRateLimiter);
        $method = $reflection->getMethod('throttleKey');
        $method->setAccessible(true);

        $request = $this->mock(
            AuthVerifyRequest::class,
            static function ($mock) {
                $mock->shouldReceive('input')->andReturn('id');
                $mock->shouldReceive('ip')->andReturn('127.0.0.1');
                $mock->shouldReceive('ceremony')->andReturn(Ceremony::Assertion);
            }
        );

        expect($method->invoke($authRateLimiter,
            $request))->toBe('auth:id|127.0.0.1');
    });

test('the user can logout of the application', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post('/logout');

    $response->assertRedirect('/');

    $this->assertGuest();
});

test('the user can logout of the application using json request', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->postJson('/logout');

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertGuest();
});

//    public function test_case_insensitive_usernames_can_be_used()
//    {
//        app('config')->set('fortify.lowercase_usernames', true);
//
//        TestAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//        ]);
//
//        $response = $this->withoutExceptionHandling()->post('/login', [
//            'email' => 'TAYLOR@LARAVEL.COM',
//            'password' => 'secret',
//        ]);
//
//        $response->assertRedirect('/home');
//    }

/**
 * Prepares user, passkey, options needed for user assertion verification.
 *
 * @param  ?callable(\Mockery\CompositeExpectation): void  $validatorMock
 * @param  ?callable(array): array  $data
 * @return array{\Illuminate\Testing\TestResponse,\App\Models\User,string,\Webauthn\PublicKeyCredential,\Webauthn\PublicKeyCredential}
 */
function prepareAssertion(callable $validatorMock = null, callable $data = null)
{
    $user = \App\Models\User::factory()->hasPasskeys(1, [
        // TODO: change name
        'name' => 'name',
    ])->create([
        'passkey_verified_at' => now(),
    ]);

    $assertion = app(AssertionCeremony::class);
    $options = $assertion->newOptions($user);
    $challenge = Challenge::fake();
    $passkey = $user->passkeys->first();
    $credentialId = $passkey->credential_id;

    $clientData = [
        'type' => 'webauthn.get',
        'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
        'origin' => app('host'),
        'crossOrigin' => false,
    ];

    $authData = random_bytes(32);

    $authenticatorAttestationResponse = AuthenticatorAssertionResponse::create(
        new CollectedClientData(
            \base64_encode(json_encode($clientData)),
            $clientData,
        ),
        new AuthenticatorData(
            $authData,
            $authData,
            '',
            0,
            null,
            null,
        ),
        'signature',
        $user->id,
    );

    $publicKeyCredential = PublicKeyCredential::create(
        $credentialId,
        'public-key',
        $credentialId,
        $authenticatorAttestationResponse
    );

    app()->instance(
        PublicKeyCredentialLoader::class,
        mock(PublicKeyCredentialLoader::class)
    )->shouldReceive('loadArray')->andReturn($publicKeyCredential);

    $publicKeyCredentialSource = $passkey->publicKeyCredentialSource();

    $validator = app()->instance(
        AuthenticatorAssertionResponseValidator::class,
        mock(AuthenticatorAssertionResponseValidator::class)
    );

    if ($validatorMock !== null) {
        $validatorMock($validator->shouldReceive('check'));
    } else {
        $validator->shouldReceive('check')
            ->with(
                IsEqual::equalTo($publicKeyCredentialSource),
                $publicKeyCredential->response,
                $options,
                app('host'),
                (string) $user->id,
            )
            ->andReturn($publicKeyCredentialSource);
    }

    session()->put(
        AssertionCeremony::OPTIONS_SESSION_KEY,
        $options
    );

    $input = [
        'id' => base64_encode($credentialId),
        'rawId' => base64_encode($credentialId),
        'type' => 'public-key',
        'response' => [
            'authenticatorData' => 'authenticatorData',
            'clientDataJSON' => 'clientDataJSON',
            'signature' => 'signature',
            'userHandle' => $user->id,
        ],
    ];

    return [
        post(route('auth'), $data ? $data($input) : $input),
        $user,
        $credentialId,
        $publicKeyCredential,
    ];
}
