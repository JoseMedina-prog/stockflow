<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class BrowserLogRouteTest extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_get_returns_204(): void
    {
        $this->get('/_boost/browser-logs')->assertNoContent();
    }

    public function test_post_returns_204(): void
    {
        $this->post('/_boost/browser-logs', ['foo' => 'bar'])->assertNoContent();
    }

    public function test_does_not_require_auth(): void
    {
        $this->get('/_boost/browser-logs')->assertNoContent();
        $this->post('/_boost/browser-logs')->assertNoContent();
    }
}
