<?php

namespace Funseco\Widgets\Tests;

use Funseco\Core\Testing\TestCase as CoreTestCase;
use Funseco\Widgets\WidgetsServiceProvider;

abstract class TestCase extends CoreTestCase
{
    protected function plugins(): array
    {
        return [WidgetsServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        if ($this->isolated()) {
            // The app's config/manialinks.php (widget styles, layout); a drift-tested copy.
            $app['config']->set('manialinks', require __DIR__.'/Support/manialinks.php');
        }
    }
}
