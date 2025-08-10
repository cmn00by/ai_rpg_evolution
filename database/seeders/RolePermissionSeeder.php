<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            'manage-system',
            'manage-attributes',
            'manage-classes',
            'manage-objects',
            'manage-shops',
            'manage-inventories',
            'view-inventories',
            'trade',
            'equip',
            'view-logs',
            'manage-users',
            'view-admin-panel'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions
        
        // Super Admin - Toutes les permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - Gestion complète sauf système
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'manage-attributes',
            'manage-classes',
            'manage-objects',
            'manage-shops',
            'manage-inventories',
            'view-inventories',
            'trade',
            'equip',
            'view-logs',
            'manage-users',
            'view-admin-panel'
        ]);

        // Staff - Gestion limitée
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->givePermissionTo([
            'manage-shops',
            'view-inventories',
            'trade',
            'equip',
            'view-logs',
            'view-admin-panel'
        ]);

        // Player - Permissions de base
        $player = Role::firstOrCreate(['name' => 'player']);
        $player->givePermissionTo([
            'trade',
            'equip'
        ]);

        // Créer les comptes administrateurs
        $this->createAdminUsers($superAdmin, $admin);
    }

    private function createAdminUsers($superAdmin, $admin)
    {
        // Super Admin
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@rpg.local'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );
        $superAdminUser->assignRole($superAdmin);

        // Admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@rpg.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );
        $adminUser->assignRole($admin);

        // Utilisateur de démonstration
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@rpg.local'],
            [
                'name' => 'Demo Player',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );
        $demoUser->assignRole('player');

        $this->command->info('Comptes créés:');
        $this->command->info('Super Admin: superadmin@rpg.local / password');
        $this->command->info('Admin: admin@rpg.local / password');
        $this->command->info('Demo Player: demo@rpg.local / password');
    }
}
