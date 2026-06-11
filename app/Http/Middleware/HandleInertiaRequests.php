<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $appName = Cache::rememberForever('app:display_name', function () {
            try {
                return \App\Models\Setting::where('key', 'business.name')->value('value') ?: config('app.name');
            } catch (\Throwable) {
                return config('app.name');
            }
        });

        $authPayload = $this->resolveAuthPayload($user, $request);

        return array_merge(parent::share($request), [
            'name' => $appName,
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => $authPayload,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    /**
     * @return array{user: mixed, roles: array<int, string>, permissions: array<int, string>}
     */
    private function resolveAuthPayload(mixed $user, Request $request): array
    {
        if (! $user) {
            return ['user' => null, 'roles' => [], 'permissions' => []];
        }

        $session = $request->session();
        $cacheKey = "auth:{$user->getAuthIdentifier()}";

        $roles = $session->get($cacheKey.'.roles');
        $permissions = $session->get($cacheKey.'.permissions');

        if (! is_array($roles) || ! is_array($permissions)) {
            $roles = $user->getRoleNames()->values()->all();
            $permissions = $user->getAllPermissions()->pluck('name')->values()->all();

            $session->put($cacheKey.'.roles', $roles);
            $session->put($cacheKey.'.permissions', $permissions);
        }

        return [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }
}
