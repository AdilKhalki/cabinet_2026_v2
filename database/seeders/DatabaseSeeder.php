<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Référentiels : nécessaires dans tous les environnements
        $this->call([
            RolesAndPermissionsSeeder::class,
            MedicalActSeeder::class,
            MedicationSeeder::class,
            LabTestTypeSeeder::class,
        ]);

        // Données fictives : jamais en production
        if (! app()->isProduction()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
