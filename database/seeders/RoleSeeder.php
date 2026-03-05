<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        // Crucial if you run this seeder multiple times!
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Create Permissions
        // Focus on specific actions (verbs)
        $permissions = [
            'view students',
            'manage students',
            'edit grades',
            'generate reports',
            // Admin only
            'manage teachers', 
            'manage subjects',
            'manage classes',
            'manage lesson plans'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 3. Create Roles and Assign Permissions
        $teacherRole = Role::create(['name' => 'teacher']);
        $teacherRole->givePermissionTo(['view students', 'edit grades', 'generate reports', 'manage students']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // 4. Optional: Assign a Role to your first user
        $admin = User::where('email', 'dane.jacksond@gmail.com')->first();
        if ($admin) {
            $admin->assignRole($adminRole);
        }
    }
}