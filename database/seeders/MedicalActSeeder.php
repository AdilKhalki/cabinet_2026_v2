<?php

namespace Database\Seeders;

use App\Models\MedicalAct;
use Illuminate\Database\Seeder;

class MedicalActSeeder extends Seeder
{
    public function run(): void
    {
        $acts = [
            ['code' => 'CS', 'name' => 'Consultation générale', 'category' => 'Consultation', 'price' => 150, 'duration_minutes' => 20],
            ['code' => 'CSP', 'name' => 'Consultation spécialisée', 'category' => 'Consultation', 'price' => 300, 'duration_minutes' => 30],
            ['code' => 'CSU', 'name' => 'Consultation urgence', 'category' => 'Consultation', 'price' => 250, 'duration_minutes' => 20],
            ['code' => 'CTL', 'name' => 'Téléconsultation', 'category' => 'Consultation', 'price' => 100, 'duration_minutes' => 20],
            ['code' => 'ECG', 'name' => 'Électrocardiogramme', 'category' => 'Examen', 'price' => 100, 'duration_minutes' => 15],
            ['code' => 'SPO2', 'name' => 'Mesure saturation O2', 'category' => 'Examen', 'price' => 30, 'duration_minutes' => 5],
            ['code' => 'CERT', 'name' => 'Certificat médical', 'category' => 'Administratif', 'price' => 50, 'duration_minutes' => 10],
            ['code' => 'ARRW', 'name' => 'Arrêt de travail', 'category' => 'Administratif', 'price' => 50, 'duration_minutes' => 10],
            ['code' => 'INJIM', 'name' => 'Injection intramusculaire', 'category' => 'Soin', 'price' => 80, 'duration_minutes' => 10],
            ['code' => 'INJIV', 'name' => 'Perfusion intraveineuse', 'category' => 'Soin', 'price' => 150, 'duration_minutes' => 30],
        ];

        foreach ($acts as $act) {
            MedicalAct::firstOrCreate(['code' => $act['code']], $act);
        }

        $this->command->info('Actes médicaux créés.');
    }
}
