<?php

namespace Qruto\Cave\Tests;

use Qruto\Cave\Features;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use WithLaravelMigrations, WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app['config']->set(['database.default' => 'testing']);
    }

    protected function withConfirmedTwoFactorAuthentication($app)
    {
        $app['config']->set('fortify.features', [
            Features::twoFactorAuthentication(['confirm' => true]),
        ]);
    }
}