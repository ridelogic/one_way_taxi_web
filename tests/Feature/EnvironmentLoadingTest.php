<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use ReflectionProperty;
use Tests\TestCase;

class EnvironmentLoadingTest extends TestCase
{
    /**
     * bootstrap/app.php must keep .env values out of the process-wide environment (putenv): on threaded PHP
     * (XAMPP's Apache module) concurrent requests otherwise lose .env values — missing APP_KEY, wrong database.
     */
    public function test_env_values_are_not_written_to_the_process_environment(): void
    {
        $putenv = new ReflectionProperty(Env::class, 'putenv');

        $this->assertFalse($putenv->getValue(), 'Env::disablePutenv() must be called in bootstrap/app.php');
        $this->assertStringContainsString('Env::disablePutenv();', file_get_contents(base_path('bootstrap/app.php')));
    }
}
