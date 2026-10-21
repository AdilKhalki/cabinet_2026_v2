<?php

namespace Database\Seeders;

use App\Models\LabTestType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabTestTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        LabTestType::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $types = [
            // Hématologie
            ['name' => 'Globules Rouges (GR)',        'unit' => 'x10^6/µL', 'normal_min' => 3.8,   'normal_max' => 5.4,   'category' => 'Hématologie'],
            ['name' => 'Hémoglobine (Hb)',             'unit' => 'g/dL',     'normal_min' => 12.0,  'normal_max' => 17.5,  'category' => 'Hématologie'],
            ['name' => 'Hématocrite (Ht)',             'unit' => '%',        'normal_min' => 36.0,  'normal_max' => 50.0,  'category' => 'Hématologie'],
            ['name' => 'Globules Blancs (GB)',         'unit' => 'x10^3/µL', 'normal_min' => 4.0,   'normal_max' => 10.0,  'category' => 'Hématologie'],
            ['name' => 'Plaquettes',                   'unit' => 'x10^3/µL', 'normal_min' => 150.0, 'normal_max' => 400.0, 'category' => 'Hématologie'],
            ['name' => 'VGM',                          'unit' => 'fL',       'normal_min' => 80.0,  'normal_max' => 100.0, 'category' => 'Hématologie'],
            ['name' => 'TCMH',                         'unit' => 'pg',       'normal_min' => 27.0,  'normal_max' => 32.0,  'category' => 'Hématologie'],

            // Biochimie
            ['name' => 'Glycémie à jeun',              'unit' => 'g/L',      'normal_min' => 0.70,  'normal_max' => 1.10,  'category' => 'Biochimie'],
            ['name' => 'HbA1c',                        'unit' => '%',        'normal_min' => 4.0,   'normal_max' => 6.0,   'category' => 'Biochimie'],
            ['name' => 'Créatinine',                   'unit' => 'mg/L',     'normal_min' => 6.0,   'normal_max' => 12.0,  'category' => 'Biochimie'],
            ['name' => 'Urée',                         'unit' => 'g/L',      'normal_min' => 0.15,  'normal_max' => 0.45,  'category' => 'Biochimie'],
            ['name' => 'Acide Urique',                 'unit' => 'mg/L',     'normal_min' => 25.0,  'normal_max' => 70.0,  'category' => 'Biochimie'],
            ['name' => 'Cholestérol Total',            'unit' => 'g/L',      'normal_min' => null,  'normal_max' => 2.0,   'category' => 'Biochimie'],
            ['name' => 'LDL Cholestérol',              'unit' => 'g/L',      'normal_min' => null,  'normal_max' => 1.60,  'category' => 'Biochimie'],
            ['name' => 'HDL Cholestérol',              'unit' => 'g/L',      'normal_min' => 0.40,  'normal_max' => null,  'category' => 'Biochimie'],
            ['name' => 'Triglycérides',                'unit' => 'g/L',      'normal_min' => null,  'normal_max' => 1.50,  'category' => 'Biochimie'],

            // Ionogramme
            ['name' => 'Sodium (Na)',                  'unit' => 'mEq/L',    'normal_min' => 135.0, 'normal_max' => 145.0, 'category' => 'Ionogramme'],
            ['name' => 'Potassium (K)',                'unit' => 'mEq/L',    'normal_min' => 3.5,   'normal_max' => 5.0,   'category' => 'Ionogramme'],
            ['name' => 'Chlore (Cl)',                  'unit' => 'mEq/L',    'normal_min' => 95.0,  'normal_max' => 105.0, 'category' => 'Ionogramme'],
            ['name' => 'Calcium (Ca)',                 'unit' => 'mg/L',     'normal_min' => 85.0,  'normal_max' => 105.0, 'category' => 'Ionogramme'],

            // Bilan hépatique
            ['name' => 'ASAT (TGO)',                   'unit' => 'UI/L',     'normal_min' => 0.0,   'normal_max' => 40.0,  'category' => 'Bilan Hépatique'],
            ['name' => 'ALAT (TGP)',                   'unit' => 'UI/L',     'normal_min' => 0.0,   'normal_max' => 40.0,  'category' => 'Bilan Hépatique'],
            ['name' => 'GGT',                          'unit' => 'UI/L',     'normal_min' => 0.0,   'normal_max' => 50.0,  'category' => 'Bilan Hépatique'],
            ['name' => 'Phosphatases Alcalines (PAL)', 'unit' => 'UI/L',     'normal_min' => 40.0,  'normal_max' => 130.0, 'category' => 'Bilan Hépatique'],
            ['name' => 'Bilirubine Totale',            'unit' => 'mg/L',     'normal_min' => 2.0,   'normal_max' => 12.0,  'category' => 'Bilan Hépatique'],
            ['name' => 'Protéines Totales',            'unit' => 'g/L',      'normal_min' => 65.0,  'normal_max' => 85.0,  'category' => 'Bilan Hépatique'],

            // Thyroïde
            ['name' => 'TSH',                          'unit' => 'µUI/mL',   'normal_min' => 0.4,   'normal_max' => 4.0,   'category' => 'Thyroïde'],
            ['name' => 'T4 Libre (FT4)',               'unit' => 'pmol/L',   'normal_min' => 12.0,  'normal_max' => 22.0,  'category' => 'Thyroïde'],
            ['name' => 'T3 Libre (FT3)',               'unit' => 'pmol/L',   'normal_min' => 3.5,   'normal_max' => 6.5,   'category' => 'Thyroïde'],

            // Inflammation
            ['name' => 'CRP (Protéine C Réactive)',    'unit' => 'mg/L',     'normal_min' => null,  'normal_max' => 6.0,   'category' => 'Inflammation'],
            ['name' => 'Vitesse de Sédimentation (VS)', 'unit' => 'mm/h',     'normal_min' => null,  'normal_max' => null,  'category' => 'Inflammation'],
            ['name' => 'Fibrinogène',                  'unit' => 'g/L',      'normal_min' => 2.0,   'normal_max' => 4.0,   'category' => 'Inflammation'],

            // Urinaire
            ['name' => 'Leucocytes urinaires (ECBU)',  'unit' => '/mm³',     'normal_min' => 0.0,   'normal_max' => 10.0,  'category' => 'Urinaire'],
            ['name' => 'Protéinurie des 24h',          'unit' => 'mg/24h',   'normal_min' => 0.0,   'normal_max' => 150.0, 'category' => 'Urinaire'],
        ];

        foreach ($types as $type) {
            LabTestType::create($type);
        }
    }
}
