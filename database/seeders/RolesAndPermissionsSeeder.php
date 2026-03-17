<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les rôles
        $admin      = Role::firstOrCreate(['name' => 'admin']);
        $medecin    = Role::firstOrCreate(['name' => 'medecin']);
        $secretaire = Role::firstOrCreate(['name' => 'secretaire']);

        // Créer les permissions
        $permissions = [
            // Patients
            'view patients', 'create patients', 'edit patients', 'delete patients',
            // Rendez-vous
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            // Consultations
            'view consultations', 'create consultations', 'edit consultations', 'delete consultations',
            // Ordonnances
            'view prescriptions', 'create prescriptions', 'print prescriptions',
            // Analyses
            'view lab-tests', 'create lab-tests', 'edit lab-tests',
            // Imagerie médicale
            'view medical-images', 'create medical-images', 'edit medical-images', 'delete medical-images',
            // Rapports médicaux
            'view medical-reports', 'create medical-reports', 'edit medical-reports', 'delete medical-reports', 'print medical-reports',
            // Paiements
            'view payments', 'create payments', 'edit payments', 'delete payments',
            // Documents
            'view documents', 'upload documents', 'delete documents',
            // Rapports financiers
            'view reports', 'generate reports',
            // Admin
            'manage users', 'manage roles', 'manage medications', 'view activity-log',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Attribuer toutes les permissions à admin
        $admin->syncPermissions(Permission::all());

        // Permissions médecin
        $medecin->syncPermissions([
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments',
            'view consultations', 'create consultations', 'edit consultations',
            'view prescriptions', 'create prescriptions', 'print prescriptions',
            'view lab-tests', 'create lab-tests', 'edit lab-tests',
            'view medical-images', 'create medical-images', 'edit medical-images', 'delete medical-images',
            'view medical-reports', 'create medical-reports', 'edit medical-reports', 'print medical-reports',
            'view documents', 'upload documents',
            'view payments',
            'view reports', 'generate reports',
        ]);

        // Permissions secrétaire
        $secretaire->syncPermissions([
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            'view payments', 'create payments', 'edit payments',
            'view documents', 'upload documents',
        ]);

        // Créer l'admin par défaut
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@cabinet.ma'],
            [
                'name'     => 'Administrateur',
                'password' => Hash::make('Admin@2026!'),
                'phone'    => '0600000000',
            ]
        );
        $adminUser->assignRole('admin');

        // Créer un médecin par défaut
        $doctor = User::firstOrCreate(
            ['email' => 'medecin@cabinet.ma'],
            [
                'name'           => 'Dr. Mohamed Alami',
                'password'       => Hash::make('Medecin@2026!'),
                'phone'          => '0600000001',
                'speciality'     => 'Médecine Générale',
                'license_number' => 'RPPS123456',
            ]
        );
        $doctor->assignRole('medecin');

        // Créer une secrétaire par défaut
        $secretary = User::firstOrCreate(
            ['email' => 'secretaire@cabinet.ma'],
            [
                'name'     => 'Fatima Secrétaire',
                'password' => Hash::make('Secret@2026!'),
                'phone'    => '0600000002',
            ]
        );
        $secretary->assignRole('secretaire');

        $this->command->info('Rôles, permissions et utilisateurs créés avec succès !');
        $this->command->table(
            ['Email', 'Rôle', 'Mot de passe'],
            [
                ['admin@cabinet.ma', 'admin', 'Admin@2026!'],
                ['medecin@cabinet.ma', 'medecin', 'Medecin@2026!'],
                ['secretaire@cabinet.ma', 'secretaire', 'Secret@2026!'],
            ]
        );
    }
}
