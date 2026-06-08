<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $config = config('stockflow_permissions');

        $permissions = $this->buildPermissionList($config);

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        foreach ($config['roles'] as $roleKey => $roleConfig) {
            $role = Role::firstOrCreate([
                'name' => $roleKey,
                'guard_name' => 'web',
            ]);

            $perms = $roleConfig['permissions'] === 'all'
                ? $permissions
                : $roleConfig['permissions'];

            $role->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function buildPermissionList(array $config): array
    {
        $list = [];

        foreach ($config['resources'] as $resource => $label) {
            foreach ($config['actions'] as $action) {
                $list[] = "{$resource}.{$action}";
            }
        }

        return $list;
    }
}
