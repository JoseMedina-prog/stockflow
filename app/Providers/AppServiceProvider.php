<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->invalidateAuthSessionCache();
    }

    private function invalidateAuthSessionCache(): void
    {
        $forget = function (object $event): void {
            $userId = $event->model?->getAuthIdentifier();

            if ($userId === null) {
                return;
            }

            if (app()->bound('session') && app('session')->isStarted()) {
                app('session')->forget([
                    "auth:{$userId}.roles",
                    "auth:{$userId}.permissions",
                ]);
            }
        };

        Event::listen(RoleAttached::class, $forget);
        Event::listen(RoleDetached::class, $forget);
        Event::listen(PermissionAttached::class, $forget);
        Event::listen(PermissionDetached::class, $forget);
    }
}
