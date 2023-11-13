<?php


use Illuminate\Contracts\Auth\Authenticatable;
use Qruto\Cave\Contracts\VerifyEmailViewResponse;
use Qruto\Cave\Tests\TestCase;

class EmailVerificationPromptControllerTest extends TestCase
{
    public function test_the_email_verification_prompt_view_is_returned()
    {
        $this->mock(VerifyEmailViewResponse::class)
                ->shouldReceive('toResponse')
                ->andReturn(response('hello world'));

        $user = Mockery::mock(Authenticatable::class);
        $user->shouldReceive('hasVerifiedEmail')->andReturn(false);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSeeText('hello world');
    }

    public function test_user_is_redirect_home_if_already_verified()
    {
        $this->mock(VerifyEmailViewResponse::class)
                ->shouldReceive('toResponse')
                ->andReturn(response('hello world'));

        $user = Mockery::mock(Authenticatable::class);
        $user->shouldReceive('hasVerifiedEmail')->andReturn(true);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertRedirect('/home');
    }

    public function test_user_is_redirect_to_intended_url_if_already_verified()
    {
        $this->mock(VerifyEmailViewResponse::class)
                ->shouldReceive('toResponse')
                ->andReturn(response('hello world'));

        $user = Mockery::mock(Authenticatable::class);
        $user->shouldReceive('hasVerifiedEmail')->andReturn(true);

        $response = $this->actingAs($user)
            ->withSession(['url.intended' => 'http://foo.com/bar'])
            ->get('/email/verify');

        $response->assertRedirect('http://foo.com/bar');
    }
}
