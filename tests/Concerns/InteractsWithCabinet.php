<?php

namespace Tests\Concerns;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Helpers partagés par les tests fonctionnels du cabinet.
 * À utiliser avec RefreshDatabase.
 */
trait InteractsWithCabinet
{
    protected function seedRoles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Crée un utilisateur avec le rôle donné : admin | medecin | secretaire */
    protected function userWithRole(string $role, array $attributes = []): User
    {
        if (! Role::where('name', $role)->exists()) {
            $this->seedRoles();
        }

        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $user->assignRole($role);

        return $user;
    }

    protected function admin(array $attributes = []): User
    {
        return $this->userWithRole('admin', $attributes);
    }

    protected function doctor(array $attributes = []): User
    {
        return $this->userWithRole('medecin', array_merge(['speciality' => 'Médecine générale'], $attributes));
    }

    protected function secretary(array $attributes = []): User
    {
        return $this->userWithRole('secretaire', $attributes);
    }

    protected function makePatient(array $attributes = []): Patient
    {
        static $n = 0;
        $n++;

        return Patient::create(array_merge([
            'first_name' => 'Patient',
            'last_name' => 'Test'.$n,
            'birth_date' => '1985-05-10',
            'gender' => 'male',
            'phone' => '06000000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
        ], $attributes));
    }
}
