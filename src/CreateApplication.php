<?php

namespace Bizarg\Test;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Trait CreateApplication
 * @package Bizarg\test\src
 */
trait CreateApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
