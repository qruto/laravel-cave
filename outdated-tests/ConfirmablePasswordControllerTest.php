<?php


use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Qruto\Cave\Cave;

class ConfirmablePasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->user = TestConfirmPasswordUser::forceCreate([
                'name' => 'Taylor Otwell',
                'email' => 'taylor@laravel.com',
                'password' => bcrypt('secret'),
            ]);
        });

        parent::setUp();
    }

    public function test_password_can_be_confirmed()
    {
        $response = $this->withoutExceptionHandling()
            ->actingAs($this->user)
            ->withSession(['url.intended' => 'http://foo.com/bar'])
            ->post(
                '/user/confirm-password',
                ['password' => 'secret']
            );

        $response->assertSessionHas('auth.password_confirmed_at');
        $response->assertRedirect('http://foo.com/bar');
    }

    public function test_password_confirmation_can_fail_with_an_invalid_password()
    {
        $response = $this->withoutExceptionHandling()
            ->actingAs($this->user)
            ->withSession(['url.intended' => 'http://foo.com/bar'])
            ->post(
                '/user/confirm-password',
                ['password' => 'invalid']
            );

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionMissing('auth.password_confirmed_at');
        $response->assertRedirect();
        $this->assertNotEquals($response->getTargetUrl(), 'http://foo.com/bar');
    }

    public function test_password_confirmation_can_fail_without_a_password()
    {
        $response = $this->withoutExceptionHandling()
            ->actingAs($this->user)
            ->withSession(['url.intended' => 'http://foo.com/bar'])
            ->post(
                '/user/confirm-password',
                ['password' => null]
            );

        $response->assertSessionHasErrors(['password']);
        $response->assertSessionMissing('auth.password_confirmed_at');
        $response->assertRedirect();
        $this->assertNotEquals($response->getTargetUrl(), 'http://foo.com/bar');
    }

    public function test_password_confirmation_can_be_customized()
    {
        Cave::$confirmPasswordsUsingCallback = function () {
            return true;
        };

        $response = $this->withoutExceptionHandling()
            ->actingAs($this->user)
            ->withSession(['url.intended' => 'http://foo.com/bar'])
            ->post(
                '/user/confirm-password',
                ['password' => 'invalid']
            );

        $response->assertSessionHas('auth.password_confirmed_at');
        $response->assertRedirect('http://foo.com/bar');

        Cave::$confirmPasswordsUsingCallback = null;
    }

    public function test_password_confirmation_can_be_customized_and_fail_without_password()
    {
        Cave::$confirmPasswordsUsingCallback = function () {
            return true;
        };

        $response = $this->withoutExceptionHandling()
            ->actingAs($this->user)
            ->withSession(['url.intended' => 'http://foo.com/bar'])
            ->post(
                '/user/confirm-password',
                ['password' => null]
            );

        $response->assertSessionHas('auth.password_confirmed_at');
        $response->assertRedirect('http://foo.com/bar');

        Cave::$confirmPasswordsUsingCallback = null;
    }

    public function test_password_can_be_confirmed_with_json()
    {
        $response = $this->actingAs($this->user)
            ->postJson(
                '/user/confirm-password',
                ['password' => 'secret']
            );

        $response->assertStatus(201);
    }

    public function test_password_confirmation_can_fail_with_json()
    {
        $response = $this->actingAs($this->user)
            ->postJson(
                '/user/confirm-password',
                ['password' => 'invalid']
            );

        $response->assertJsonValidationErrors('password');
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set([
            'auth.providers.users.model' => TestConfirmPasswordUser::class,
        ]);
    }
}

class TestConfirmPasswordUser extends User
{
    protected $table = 'users';
}
