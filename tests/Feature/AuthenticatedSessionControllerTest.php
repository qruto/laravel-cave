<?php

namespace Qruto\Cave\Tests;

use App\Models\User;
use Hamcrest\Core\IsEqual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\CompositeExpectation;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Qruto\Cave\Authenticators\AssertionCeremony;
use Qruto\Cave\Authenticators\AttestationCeremony;
use Qruto\Cave\Challenge;
use Qruto\Cave\Contracts\AuthViewResponse;
use Qruto\Cave\Models\Passkey;
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
    [$response] = prepareAssertion(data: fn (array $data) => array_filter($data, fn ($key) => $key !== 'rawId', ARRAY_FILTER_USE_KEY));

    $response->assertSessionHas(AssertionCeremony::OPTIONS_SESSION_KEY);

    $response->assertSessionHasErrors('rawId');
});

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

//    public function test_user_can_authenticate()
//    {
//        TestAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//        ]);
//
//        $response = $this->withoutExceptionHandling()->post('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'secret',
//        ]);
//
//        $response->assertRedirect('/home');
//    }
//
//    public function test_user_is_redirected_to_challenge_when_using_two_factor_authentication()
//    {
//        Event::fake();
//
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_secret' => 'test-secret',
//        ]);
//
//        $response = $this->withoutExceptionHandling()->post('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'secret',
//        ]);
//
//        $response->assertRedirect('/two-factor-challenge');
//
//        Event::assertDispatched(TwoFactorAuthenticationChallenged::class);
//    }
//
//    public function test_user_is_not_redirected_to_challenge_when_using_two_factor_authentication_that_has_not_been_confirmed_and_confirmation_is_enabled()
//    {
//        Event::fake();
//
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//        app('config')->set('fortify.features', [
//            Features::registration(),
//            Features::twoFactorAuthentication(['confirm' => true]),
//        ]);
//
//        TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_secret' => 'test-secret',
//        ]);
//
//        $response = $this->withoutExceptionHandling()->post('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'secret',
//        ]);
//
//        $response->assertRedirect('/home');
//    }
//
//    public function test_user_is_redirected_to_challenge_when_using_two_factor_authentication_that_has_been_confirmed_and_confirmation_is_enabled()
//    {
//        Event::fake();
//
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//        app('config')->set('fortify.features', [
//            Features::registration(),
//            Features::twoFactorAuthentication(['confirm' => true]),
//        ]);
//
//        Schema::table('users', function ($table) {
//            $table->timestamp('two_factor_confirmed_at')->nullable();
//        });
//
//        TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_secret' => 'test-secret',
//            'two_factor_confirmed_at' => now(),
//        ]);
//
//        $response = $this->withoutExceptionHandling()->post('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'secret',
//        ]);
//
//        $response->assertRedirect('/two-factor-challenge');
//    }
//
//    public function test_user_can_authenticate_when_two_factor_challenge_is_disabled()
//    {
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        $features = app('config')->get('fortify.features');
//
//        unset($features[array_search(Features::twoFactorAuthentication(), $features)]);
//
//        app('config')->set('fortify.features', $features);
//
//        TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_secret' => 'test-secret',
//        ]);
//
//        $response = $this->withoutExceptionHandling()->post('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'secret',
//        ]);
//
//        $response->assertRedirect('/home');
//    }
//
//    public function test_validation_exception_returned_on_failure()
//    {
//        TestAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//        ]);
//
//        $response = $this->post('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'password',
//        ]);
//
//        $response->assertStatus(302);
//        $response->assertSessionHasErrors(['email']);
//    }
//
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

/**
 * @dataProvider usernameProvider
 */
//    public function test_cant_bypass_throttle_with_special_characters(string $username, string $expectedResult)
//    {
//        $loginRateLimiter = new LoginRateLimiter(
//            $this->mock(RateLimiter::class)
//        );
//
//        $reflection = new \ReflectionClass($loginRateLimiter);
//        $method = $reflection->getMethod('throttleKey');
//        $method->setAccessible(true);
//
//        $request = $this->mock(
//            Request::class,
//            static function ($mock) use ($username) {
//                $mock->shouldReceive('input')->andReturn($username);
//                $mock->shouldReceive('ip')->andReturn('192.168.0.1');
//            }
//        );
//
//        self::assertSame($expectedResult.'|192.168.0.1', $method->invoke($loginRateLimiter, $request));
//    }
//
//    public function usernameProvider(): array
//    {
//        return [
//            'lowercase special characters' => ['ⓣⓔⓢⓣ@ⓛⓐⓡⓐⓥⓔⓛ.ⓒⓞⓜ', 'test@laravel.com'],
//            'uppercase special characters' => ['ⓉⒺⓈⓉ@ⓁⒶⓇⒶⓋⒺⓁ.ⒸⓄⓂ', 'test@laravel.com'],
//            'special character numbers' =>['test⑩⓸③@laravel.com', 'test1043@laravel.com'],
//            'default email' => ['test@laravel.com', 'test@laravel.com'],
//        ];
//    }
//
//    public function test_the_user_can_logout_of_the_application()
//    {
//        Auth::guard()->setUser(
//            Mockery::mock(Authenticatable::class)->shouldIgnoreMissing()
//        );
//
//        $response = $this->post('/logout');
//
//        $response->assertRedirect('/');
//        $this->assertNull(Auth::guard()->getUser());
//    }
//
//    public function test_the_user_can_logout_of_the_application_using_json_request()
//    {
//        Auth::guard()->setUser(
//            Mockery::mock(Authenticatable::class)->shouldIgnoreMissing()
//        );
//
//        $response = $this->postJson('/logout');
//
//        $response->assertStatus(204);
//        $this->assertNull(Auth::guard()->getUser());
//    }
//
//    public function test_two_factor_challenge_can_be_passed_via_code()
//    {
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        $tfaEngine = app(Google2FA::class);
//        $userSecret = $tfaEngine->generateSecretKey();
//        $validOtp = $tfaEngine->getCurrentOtp($userSecret);
//
//        $user = TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_secret' => encrypt($userSecret),
//        ]);
//
//        $response = $this->withSession([
//            'login.id' => $user->id,
//            'login.remember' => false,
//        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
//            'code' => $validOtp,
//        ]);
//
//        $response->assertRedirect('/home')
//            ->assertSessionMissing('login.id');
//    }
//
//    public function test_two_factor_authentication_preserves_remember_me_selection(): void
//    {
//        Event::fake();
//
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_secret' => 'test-secret',
//        ]);
//
//        $response = $this->withoutExceptionHandling()->post('/login', [
//            'email' => 'taylor@laravel.com',
//            'password' => 'secret',
//            'remember' => false,
//        ]);
//
//        $response->assertRedirect('/two-factor-challenge')
//            ->assertSessionHas('login.remember', false);
//    }
//
//    public function test_two_factor_challenge_fails_for_old_otp_and_zero_window()
//    {
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        //Setting window to 0 should mean any old OTP is instantly invalid
//        app('config')->set('fortify.features', [
//            Features::twoFactorAuthentication(['window' => 0]),
//        ]);
//
//        $tfaEngine = app(Google2FA::class);
//        $userSecret = $tfaEngine->generateSecretKey();
//        $currentTs = $tfaEngine->getTimestamp();
//        $previousOtp = $tfaEngine->oathTotp($userSecret, $currentTs - 1);
//
//        $user = TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_secret' => encrypt($userSecret),
//        ]);
//
//        $response = $this->withSession([
//            'login.id' => $user->id,
//            'login.remember' => false,
//        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
//            'code' => $previousOtp,
//        ]);
//
//        $response->assertRedirect('/two-factor-challenge')
//                 ->assertSessionHas('login.id')
//                 ->assertSessionHasErrors(['code']);
//    }
//
//    public function test_two_factor_challenge_can_be_passed_via_recovery_code()
//    {
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        $user = TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_recovery_codes' => encrypt(json_encode(['invalid-code', 'valid-code'])),
//        ]);
//
//        $response = $this->withSession([
//            'login.id' => $user->id,
//            'login.remember' => false,
//        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
//            'recovery_code' => 'valid-code',
//        ]);
//
//        $response->assertRedirect('/home')
//            ->assertSessionMissing('login.id');
//        $this->assertNotNull(Auth::getUser());
//        $this->assertNotContains('valid-code', json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true));
//    }
//
//    public function test_two_factor_challenge_can_fail_via_recovery_code()
//    {
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        $user = TestTwoFactorAuthenticationSessionUser::forceCreate([
//            'name' => 'Taylor Otwell',
//            'email' => 'taylor@laravel.com',
//            'password' => bcrypt('secret'),
//            'two_factor_recovery_codes' => encrypt(json_encode(['invalid-code', 'valid-code'])),
//        ]);
//
//        $response = $this->withSession([
//            'login.id' => $user->id,
//            'login.remember' => false,
//        ])->withoutExceptionHandling()->post('/two-factor-challenge', [
//            'recovery_code' => 'missing-code',
//        ]);
//
//        $response->assertRedirect('/two-factor-challenge')
//            ->assertSessionHas('login.id')
//            ->assertSessionHasErrors(['recovery_code']);
//        $this->assertNull(Auth::getUser());
//    }
//
//    public function test_two_factor_challenge_requires_a_challenged_user()
//    {
//        app('config')->set('auth.providers.users.model', TestTwoFactorAuthenticationSessionUser::class);
//
//        $response = $this->withSession([])->withoutExceptionHandling()->get('/two-factor-challenge');
//
//        $response->assertRedirect('/login');
//        $this->assertNull(Auth::getUser());
//    }

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

class TestAuthenticationSessionUser extends User
{
    protected $table = 'users';
}
