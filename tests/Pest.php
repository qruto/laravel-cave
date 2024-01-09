<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Qruto\Cave\Contracts\CreatesNewUsers;
use Qruto\Cave\Models\User as AuthUser;
use Qruto\Cave\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function mockCreatesNewUsers(): void
{
    app()->instance(CreatesNewUsers::class,
        new class implements CreatesNewUsers
        {
            public function create(array $input, AuthUser $user): AuthUser
            {
                // Chose how to fill user name / recommend to remove it
                return $user->fill(['name' => 'Rick Sanchez']);
            }
        });
}
