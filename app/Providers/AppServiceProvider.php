<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerRouteMacros();
        $this->invalidateAuthSessionCache();
    }

    private function registerRouteMacros(): void
    {
        Route::macro('permissionResource', function (
            string $name,
            string $controller,
            ?array $only = null,
            ?array $except = null,
            ?string $param = null,
        ) {
            $options = [];
            if ($only !== null) {
                $options['only'] = $only;
            }
            if ($except !== null) {
                $options['except'] = $except;
            }
            if ($param !== null) {
                $options['parameters'] = [$name => $param];
            }

            return Route::resource($name, $controller, $options)
                ->middleware("permission:{$name}.view_any");
        });

        Route::macro('permissionAction', function (
            string $name,
            string $action,
            string $method,
            string $controller,
            string $ability = 'update',
            ?string $routeName = null,
        ) {
            $resolvedName = $routeName ?? "{$action}";

            return Route::{$method}($name, [$controller, $action])
                ->middleware("permission:{$ability}")
                ->name($resolvedName);
        });
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
