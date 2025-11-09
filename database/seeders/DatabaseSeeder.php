<?php

namespace Database\Seeders;

use App\Domains\Users\Seeders\RoleSeeder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@crm.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
        $admin->syncRoles(['admin']);

        $manager = User::updateOrCreate(
            ['email' => 'manager@crm.com'],
            [
                'name' => 'Regional Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );
        $manager->syncRoles(['manager']);

        $salesRep = User::updateOrCreate(
            ['email' => 'sales@crm.com'],
            [
                'name' => 'Top Sales Rep',
                'password' => Hash::make('password'),
                'role' => 'sales_rep',
                'manager_id' => $manager->id,
            ]
        );
        $salesRep->syncRoles(['sales_rep']);
    }
}
