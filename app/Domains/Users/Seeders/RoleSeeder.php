<?php

namespace App\Domains\Users\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
            'communications.create',
            'followups.manage',
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'admin' => $permissions,
            'manager' => [
                'clients.view',
                'clients.create',
                'clients.update',
                'communications.create',
                'followups.manage',
                'dashboard.view',
            ],
            'sales_rep' => [
                'clients.view',
                'communications.create',
                'followups.manage',
            ],
        ];

        foreach ($roles as $role => $assignedPermissions) {
            $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $roleModel->syncPermissions($assignedPermissions);
        }
    }
}
