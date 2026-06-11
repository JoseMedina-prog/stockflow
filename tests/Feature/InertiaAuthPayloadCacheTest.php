<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaAuthPayloadCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_and_roles_are_cached_in_session_for_repeated_requests(): void
    {
        $user = $this->vendedor();
        $this->actingAs($user);

        $this->get('/dashboard')->assertStatus(200);

        $sessionKey = "auth:{$user->getAuthIdentifier()}";
        $this->assertNotEmpty(session($sessionKey.'.roles'));
        $this->assertNotEmpty(session($sessionKey.'.permissions'));
        $this->assertContains('vendedor', session($sessionKey.'.roles'));
        $this->assertContains('sales.view_any', session($sessionKey.'.permissions'));
    }

    public function test_listener_is_registered_for_role_events(): void
    {
        $listeners = \Illuminate\Support\Facades\Event::getRawListeners()[\Spatie\Permission\Events\RoleAttached::class] ?? [];

        $this->assertNotEmpty($listeners, 'No listener registered for RoleAttached event.');
    }

    public function test_dashboard_does_not_call_get_all_permissions_on_warm_cache(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        $this->get('/dashboard')->assertStatus(200);

        $sessionKey = "auth:{$user->getAuthIdentifier()}";
        session()->put($sessionKey.'.roles', ['admin']);
        session()->put($sessionKey.'.permissions', ['dashboard.view_any']);

        User::query()->where('id', $user->id)->update(['name' => 'Otro Nombre']);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $this->assertSame(['admin'], session($sessionKey.'.roles'));
        $this->assertSame(['dashboard.view_any'], session($sessionKey.'.permissions'));
    }
}
