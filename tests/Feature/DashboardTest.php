<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = $this->vendedor();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_deferred_props_are_excluded_from_initial_response()
    {
        $user = $this->vendedor();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $props = $response->viewData('page')['props'];

        $this->assertArrayHasKey('stats', $props);
        $this->assertArrayNotHasKey('chart', $props);
        $this->assertArrayNotHasKey('recentSales', $props);
        $this->assertArrayNotHasKey('lowStockProducts', $props);
        $this->assertArrayNotHasKey('accountsReceivable', $props);
        $this->assertArrayNotHasKey('accountsPayable', $props);
        $this->assertArrayNotHasKey('topCustomers', $props);
        $this->assertArrayNotHasKey('topProducts', $props);
        $this->assertArrayNotHasKey('myTasks', $props);
        $this->assertArrayNotHasKey('myTasksSummary', $props);
    }

    public function test_dashboard_renders_without_500_when_no_data_exists()
    {
        $user = $this->vendedor();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('data-page');
    }
}
