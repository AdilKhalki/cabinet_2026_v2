<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        $medications = [
            ['name' => 'Doliprane 1000mg', 'generic_name' => 'Paracétamol', 'dosage_form' => 'Comprimé', 'strength' => '1000mg'],
            ['name' => 'Doliprane 500mg', 'generic_name' => 'Paracétamol', 'dosage_form' => 'Comprimé', 'strength' => '500mg'],
            ['name' => 'Ibuprofène 400mg', 'generic_name' => 'Ibuprofène', 'dosage_form' => 'Comprimé', 'strength' => '400mg'],
            ['name' => 'Amoxicilline 500mg', 'generic_name' => 'Amoxicilline', 'dosage_form' => 'Gélule', 'strength' => '500mg'],
            ['name' => 'Augmentin 1g', 'generic_name' => 'Amoxicilline + Clavulanate', 'dosage_form' => 'Comprimé', 'strength' => '1g'],
            ['name' => 'Oméprazole 20mg', 'generic_name' => 'Oméprazole', 'dosage_form' => 'Gélule', 'strength' => '20mg'],
            ['name' => 'Metformine 500mg', 'generic_name' => 'Metformine', 'dosage_form' => 'Comprimé', 'strength' => '500mg'],
            ['name' => 'Ramipril 5mg', 'generic_name' => 'Ramipril', 'dosage_form' => 'Comprimé', 'strength' => '5mg'],
            ['name' => 'Amlodipine 5mg', 'generic_name' => 'Amlodipine', 'dosage_form' => 'Comprimé', 'strength' => '5mg'],
            ['name' => 'Atorvastatine 20mg', 'generic_name' => 'Atorvastatine', 'dosage_form' => 'Comprimé', 'strength' => '20mg'],
            ['name' => 'Ventoline 100µg', 'generic_name' => 'Salbutamol', 'dosage_form' => 'Inhalateur', 'strength' => '100µg/dose'],
            ['name' => 'Cortancyl 5mg', 'generic_name' => 'Prednisone', 'dosage_form' => 'Comprimé', 'strength' => '5mg'],
            ['name' => 'Levothyrox 50µg', 'generic_name' => 'Lévothyroxine', 'dosage_form' => 'Comprimé', 'strength' => '50µg'],
            ['name' => 'Aspégic 100mg', 'generic_name' => 'Acide acétylsalicylique', 'dosage_form' => 'Sachet', 'strength' => '100mg'],
            ['name' => 'Rivotril 0.5mg', 'generic_name' => 'Clonazépam', 'dosage_form' => 'Comprimé', 'strength' => '0.5mg'],
        ];

        foreach ($medications as $med) {
            Medication::firstOrCreate(['name' => $med['name']], $med);
        }

        $this->command->info('Médicaments créés : '.count($medications));
    }
}
