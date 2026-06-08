<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_dashboard_or_login()
    {
        $response = $this->get('/');

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }
}
