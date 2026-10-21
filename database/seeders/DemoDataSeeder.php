<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\MedicalReport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private array $doctorIds = [];

    private array $secretaireIds = [];

    private array $patientIds = [];

    private array $consultationIds = [];

    private array $medicationIds = [];

    private array $medicalActIds = [];

    private array $labTestTypeIds = [];

    // ── Données marocaines ────────────────────────────────────────────────
    private array $prenomsMasculins = [
        'Mohamed', 'Ahmed', 'Youssef', 'Khalid', 'Hassan', 'Abdellatif', 'Rachid',
        'Said', 'Omar', 'Ali', 'Hamid', 'Karim', 'Nabil', 'Jamal', 'Fouad',
        'Mustapha', 'Tarik', 'Mehdi', 'Soufiane', 'Ilyass', 'Amine', 'Bilal',
        'Zakaria', 'Othmane', 'Ayoub',
    ];

    private array $prenomsFeminins = [
        'Fatima', 'Aicha', 'Khadija', 'Zineb', 'Leila', 'Nadia', 'Samira',
        'Hafida', 'Meryem', 'Soumia', 'Houda', 'Amina', 'Sanaa', 'Siham',
        'Wafaa', 'Naima', 'Radia', 'Loubna', 'Sara', 'Hind', 'Imane', 'Ghita',
        'Rajaa', 'Bouchra', 'Kawtar',
    ];

    private array $noms = [
        'Benali', 'El Amrani', 'Berrada', 'Tazi', 'Fassi', 'Alaoui', 'Chraibi',
        'Sefrioui', 'Kettani', 'Squalli', 'Filali', 'Bennani', 'Benaissa', 'Tahiri',
        'Ouazzani', 'Bensouda', 'Lahlou', 'Belcaid', 'Idrissi', 'Boukhrissi',
        'Charaf', 'El Hajji', 'Guerraoui', 'Haddad', 'Ziane', 'El Mansouri',
        'Benkirane', 'Saidi', 'Raji', 'Amrani',
    ];

    private array $villes = [
        'Casablanca', 'Rabat', 'Fès', 'Marrakech', 'Tanger', 'Agadir',
        'Meknès', 'Oujda', 'Kénitra', 'Tétouan', 'El Jadida', 'Béni Mellal',
        'Mohammedia', 'Settat', 'Khémisset',
    ];

    private array $groupesSanguins = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private array $professions = [
        'Enseignant', 'Fonctionnaire', 'Commerçant', 'Ingénieur', 'Médecin',
        'Retraité', 'Étudiant', 'Agriculteur', 'Artisan', 'Sans profession',
        'Infirmier', 'Pharmacien', 'Comptable', 'Avocat', 'Architecte',
    ];

    private array $assurances = [
        null, null, 'CNOPS', 'CNSS', 'RAMED', 'FAR', 'Allianz Maroc',
        'AXA Assurance', 'Saham Assurance', 'RMA Watanya',
    ];

    private array $motifs = [
        'Fièvre persistante depuis 3 jours',
        'Douleurs abdominales',
        'Toux et dyspnée',
        'Contrôle tension artérielle',
        'Renouvellement ordonnance',
        'Douleurs articulaires',
        'Céphalées récurrentes',
        'Bilan de santé annuel',
        'Fatigue générale',
        'Douleurs lombaires',
        'Palpitations cardiaques',
        'Suivi diabète',
        'Suivi hypertension',
        'Dermatose prurigineuse',
        'Rhinorrhée et obstruction nasale',
    ];

    private array $diagnostics = [
        'Hypertension artérielle essentielle' => 'I10',
        'Diabète de type 2' => 'E11',
        'Infection des voies respiratoires supérieures' => 'J06.9',
        'Lombalgie chronique' => 'M54.5',
        'Gastrite chronique' => 'K29.5',
        'Bronchite aiguë' => 'J20.9',
        'Migraine sans aura' => 'G43.0',
        'Anémie ferriprive' => 'D50.9',
        'Hypothyroïdie' => 'E03.9',
        'Arthrose du genou' => 'M17',
        'Sinusite aiguë' => 'J01.9',
        'Dyslipidémie' => 'E78.5',
        'Reflux gastro-œsophagien' => 'K21.0',
        'Anxiété généralisée' => 'F41.1',
        'Infection urinaire' => 'N39.0',
    ];

    private array $labos = [
        'Laboratoire Ibn Sina', 'Laboratoire Atlas', 'Laboratoire Al Andalous',
        'Biochimie Centrale', 'Laboratoire du Centre', 'Labotest Maroc',
    ];

    // ═════════════════════════════════════════════════════════════════════
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->command->info('Chargement des données existantes...');
        $this->loadExistingIds();

        $this->command->info('Création des médecins...');
        $this->seedDoctors();

        $this->command->info('Création des 50 patients...');
        $this->seedPatients();

        $this->command->info('Création des 100 rendez-vous...');
        $this->seedAppointments();

        $this->command->info('Création des 200 consultations...');
        $this->seedConsultations();

        $this->command->info('Création des ordonnances...');
        $this->seedPrescriptions();

        $this->command->info('Création des analyses...');
        $this->seedLabTests();

        $this->command->info('Création des images médicales...');
        $this->seedMedicalImages();

        $this->command->info('Création des documents...');
        $this->seedDocuments();

        $this->command->info('Création des rapports médicaux...');
        $this->seedMedicalReports();

        $this->command->info('Création des paiements...');
        $this->seedPayments();

        $this->command->info('Création des antécédents médicaux...');
        $this->seedMedicalHistories();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Seeding terminé avec succès !');
        $this->command->table(
            ['Table', 'Enregistrements'],
            [
                ['Médecins',            count($this->doctorIds)],
                ['Patients',            count($this->patientIds)],
                ['Rendez-vous',         '100'],
                ['Consultations',       count($this->consultationIds)],
                ['Ordonnances',         '~'.round(count($this->consultationIds) * 0.6)],
                ['Analyses',            '~'.round(count($this->consultationIds) * 0.4)],
                ['Images médicales',    '~60'],
                ['Documents',           '~50'],
                ['Rapports médicaux',   '~30'],
                ['Paiements',           '~'.round(count($this->consultationIds) * 0.7)],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    private function loadExistingIds(): void
    {
        $this->medicationIds = DB::table('medications')->where('is_active', true)->pluck('id')->toArray();
        $this->medicalActIds = DB::table('medical_acts')->where('is_active', true)->pluck('id')->toArray();
        $this->labTestTypeIds = DB::table('lab_test_types')->where('is_active', true)->pluck('id')->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedDoctors(): void
    {
        $doctors = [
            ['name' => 'Dr. Karim Benali',     'email' => 'dr.benali@cabinet.ma',     'speciality' => 'Médecine générale'],
            ['name' => 'Dr. Fatima El Amrani', 'email' => 'dr.elamrani@cabinet.ma',   'speciality' => 'Cardiologie'],
            ['name' => 'Dr. Youssef Tazi',     'email' => 'dr.tazi@cabinet.ma',       'speciality' => 'Médecine interne'],
        ];

        foreach ($doctors as $d) {
            $existing = DB::table('users')->where('email', $d['email'])->first();
            if (! $existing) {
                $id = DB::table('users')->insertGetId([
                    'name' => $d['name'],
                    'email' => $d['email'],
                    'password' => Hash::make('password'),
                    'speciality' => $d['speciality'],
                    'phone' => '06'.rand(10000000, 99999999),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Assign role medecin
                $role = DB::table('roles')->where('name', 'medecin')->first();
                if ($role) {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $role->id,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $id,
                    ]);
                }
                $this->doctorIds[] = $id;
            } else {
                $this->doctorIds[] = $existing->id;
            }
        }

        // Admin
        $admin = DB::table('users')->where('email', 'admin@cabinet.ma')->first();
        if (! $admin) {
            $id = DB::table('users')->insertGetId([
                'name' => 'Admin Cabinet',
                'email' => 'admin@cabinet.ma',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $role = DB::table('roles')->where('name', 'admin')->first();
            if ($role) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $id,
                ]);
            }
        }

        // Secrétaire
        $sec = DB::table('users')->where('email', 'secretaire@cabinet.ma')->first();
        if (! $sec) {
            $id = DB::table('users')->insertGetId([
                'name' => 'Nadia Bennani',
                'email' => 'secretaire@cabinet.ma',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $role = DB::table('roles')->where('name', 'secretaire')->first();
            if ($role) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $id,
                ]);
            }
            $this->secretaireIds[] = $id;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedPatients(): void
    {
        $now = now();
        $offset = DB::table('patients')->count();

        for ($i = 1; $i <= 50; $i++) {
            $isMale = rand(0, 1);
            $firstName = $isMale
                ? $this->prenomsMasculins[array_rand($this->prenomsMasculins)]
                : $this->prenomsFeminins[array_rand($this->prenomsFeminins)];
            $lastName = $this->noms[array_rand($this->noms)];
            $birthDate = Carbon::now()->subYears(rand(18, 80))->subDays(rand(0, 365));
            $ville = $this->villes[array_rand($this->villes)];
            $assurance = $this->assurances[array_rand($this->assurances)];
            $createdAt = Carbon::now()->subDays(rand(30, 540));

            $dossierNum = sprintf('PAT-%s-%04d', date('Y'), $offset + $i);

            $id = DB::table('patients')->insertGetId([
                'dossier_number' => $dossierNum,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birth_date' => $birthDate->format('Y-m-d'),
                'gender' => $isMale ? 'male' : 'female',
                'phone' => '06'.rand(10000000, 99999999),
                'phone_secondary' => rand(0, 1) ? '05'.rand(10000000, 99999999) : null,
                'email' => strtolower($firstName.'.'.$lastName.$i.'@gmail.com'),
                'address' => rand(10, 999).', Rue '.$this->noms[array_rand($this->noms)],
                'city' => $ville,
                'blood_group' => rand(0, 1) ? $this->groupesSanguins[array_rand($this->groupesSanguins)] : null,
                'known_allergies' => rand(0, 4) === 0 ? 'Pénicilline' : (rand(0, 4) === 0 ? 'Aspirine' : null),
                'profession' => $this->professions[array_rand($this->professions)],
                'insurance_name' => $assurance,
                'insurance_number' => $assurance ? strtoupper(Str::random(8)) : null,
                'is_active' => true,
                'created_by' => $this->doctorIds[array_rand($this->doctorIds)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $this->patientIds[] = $id;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedAppointments(): void
    {
        $statuses = ['planned', 'confirmed', 'waiting', 'completed', 'completed', 'completed', 'cancelled', 'no_show'];
        $types = ['general', 'general', 'general', 'specialized', 'followup', 'control', 'emergency'];
        $durations = [15, 20, 30, 30, 45, 60];

        for ($i = 0; $i < 100; $i++) {
            $doctorId = $this->doctorIds[array_rand($this->doctorIds)];
            $patientId = $this->patientIds[array_rand($this->patientIds)];
            $status = $statuses[array_rand($statuses)];

            // Past or future appointments
            $daysOffset = rand(-180, 30);
            $hour = rand(8, 17);
            $minute = [0, 15, 30, 45][rand(0, 3)];
            $scheduledAt = Carbon::now()->addDays($daysOffset)->setTime($hour, $minute);

            $isPast = $daysOffset < 0;
            if (! $isPast && in_array($status, ['completed', 'waiting', 'in_progress'])) {
                $status = 'planned';
            }

            DB::table('appointments')->insert([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'created_by' => $doctorId,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $durations[array_rand($durations)],
                'consultation_type' => $types[array_rand($types)],
                'status' => $status,
                'notes' => rand(0, 2) === 0 ? $this->motifs[array_rand($this->motifs)] : null,
                'reminder_sent' => $isPast ? rand(0, 1) : 0,
                'arrived_at' => ($isPast && in_array($status, ['completed', 'waiting', 'in_progress']))
                    ? $scheduledAt->copy()->addMinutes(rand(-5, 15)) : null,
                'started_at' => ($isPast && $status === 'completed')
                    ? $scheduledAt->copy()->addMinutes(rand(0, 20)) : null,
                'ended_at' => ($isPast && $status === 'completed')
                    ? $scheduledAt->copy()->addMinutes(rand(30, 60)) : null,
                'color' => ['#01a9ac', '#e74c3c', '#3498db', '#2ecc71', '#f39c12'][rand(0, 4)],
                'created_at' => $scheduledAt->copy()->subDays(rand(1, 14)),
                'updated_at' => $scheduledAt,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedConsultations(): void
    {
        $types = ['general', 'general', 'general', 'specialized', 'followup', 'control', 'emergency'];
        $diagnosticsKeys = array_keys($this->diagnostics);
        $bps = ['110/70', '120/80', '130/85', '140/90', '150/95', '160/100', '105/65'];

        for ($i = 0; $i < 200; $i++) {
            $doctorId = $this->doctorIds[array_rand($this->doctorIds)];
            $patientId = $this->patientIds[array_rand($this->patientIds)];
            $daysAgo = rand(1, 365);
            $date = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 17), [0, 15, 30][rand(0, 2)]);

            $diagText = $diagnosticsKeys[array_rand($diagnosticsKeys)];
            $icd10 = $this->diagnostics[$diagText];

            $weight = rand(0, 1) ? rand(50, 110) + (rand(0, 9) / 10) : null;
            $height = rand(0, 1) ? rand(155, 190) : null;

            $id = DB::table('consultations')->insertGetId([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_id' => null,
                'consultation_type' => $types[array_rand($types)],
                'status' => rand(0, 4) === 0 ? 'in_progress' : 'completed',
                'consultation_date' => $date,
                'reason' => $this->motifs[array_rand($this->motifs)],
                'symptoms' => rand(0, 1) ? 'Patient se plaint de '.strtolower($this->motifs[array_rand($this->motifs)]).' depuis '.rand(1, 14).' jours.' : null,
                'clinical_exam' => rand(0, 1) ? 'Examen général satisfaisant. Auscultation cardio-pulmonaire normale. Abdomen souple.' : null,
                'weight_kg' => $weight,
                'height_cm' => $height,
                'blood_pressure' => rand(0, 1) ? $bps[array_rand($bps)] : null,
                'heart_rate' => rand(0, 1) ? rand(60, 100) : null,
                'temperature' => rand(0, 1) ? (36 + rand(0, 30) / 10) : null,
                'spo2' => rand(0, 1) ? rand(95, 100) : null,
                'diagnosis' => $diagText,
                'icd10_code' => $icd10,
                'treatment_plan' => rand(0, 1) ? 'Traitement médical adapté. Repos. Hydratation suffisante. Réévaluation à J+'.rand(7, 30).'.' : null,
                'doctor_notes' => rand(0, 3) === 0 ? 'Patient coopératif. Bon état général.' : null,
                'summary' => rand(0, 1) ? $diagText.'. Traitement prescrit.' : null,
                'followup_required' => rand(0, 3) === 0,
                'followup_date' => rand(0, 3) === 0 ? $date->copy()->addDays(rand(7, 60))->format('Y-m-d') : null,
                'created_by' => $doctorId,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $this->consultationIds[] = $id;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedPrescriptions(): void
    {
        if (empty($this->medicationIds)) {
            $this->command->warn('Aucun médicament trouvé — ordonnances ignorées.');

            return;
        }

        $routes = ['Oral', 'Oral', 'Oral', 'Injectable', 'Topique'];
        $frequencies = ['1 fois/jour', '2 fois/jour', '3 fois/jour', 'Matin et soir', 'Matin, midi et soir', 'Au besoin'];
        $count = 0;

        foreach ($this->consultationIds as $idx => $consultId) {
            if (rand(0, 9) >= 6) {
                continue;
            } // ~60% des consultations

            $consult = DB::table('consultations')->where('id', $consultId)->first();
            $prescDate = Carbon::parse($consult->consultation_date)->format('Y-m-d');

            $year = date('Y', strtotime($prescDate));
            $prescNumber = sprintf('ORD-%s-%05d', $year, $count + 1);

            $prescId = DB::table('prescriptions')->insertGetId([
                'prescription_number' => $prescNumber,
                'patient_id' => $consult->patient_id,
                'doctor_id' => $consult->doctor_id,
                'consultation_id' => $consultId,
                'prescribed_at' => $prescDate,
                'valid_until' => Carbon::parse($prescDate)->addDays(rand(30, 90))->format('Y-m-d'),
                'notes' => rand(0, 2) === 0 ? 'À prendre avec les repas. Éviter l\'alcool.' : null,
                'is_printed' => rand(0, 1),
                'created_by' => $consult->doctor_id,
                'created_at' => $consult->consultation_date,
                'updated_at' => $consult->consultation_date,
            ]);
            $count++;

            // 2-4 lignes par ordonnance
            $nbLines = rand(2, 4);
            $usedMeds = [];
            for ($l = 0; $l < $nbLines; $l++) {
                $medId = $this->medicationIds[array_rand($this->medicationIds)];
                if (in_array($medId, $usedMeds)) {
                    continue;
                }
                $usedMeds[] = $medId;

                $med = DB::table('medications')->where('id', $medId)->first();
                DB::table('prescription_lines')->insert([
                    'prescription_id' => $prescId,
                    'medication_id' => $medId,
                    'medication_name' => $med->name.($med->strength ? ' '.$med->strength : ''),
                    'dosage' => '1 '.($med->dosage_form ?? 'comprimé'),
                    'frequency' => $frequencies[array_rand($frequencies)],
                    'duration_days' => rand(5, 30),
                    'route' => $routes[array_rand($routes)],
                    'instructions' => rand(0, 2) === 0 ? 'À prendre pendant les repas' : null,
                    'quantity' => rand(1, 3),
                    'sort_order' => $l,
                    'created_at' => $consult->consultation_date,
                    'updated_at' => $consult->consultation_date,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedLabTests(): void
    {
        if (empty($this->labTestTypeIds)) {
            $this->command->warn('Aucun type d\'analyse trouvé — analyses ignorées.');

            return;
        }

        $statuses = ['completed', 'completed', 'completed', 'pending', 'requested'];
        $count = 0;

        foreach ($this->consultationIds as $consultId) {
            if (rand(0, 9) >= 4) {
                continue;
            } // ~40% des consultations

            $consult = DB::table('consultations')->where('id', $consultId)->first();
            $reqDate = Carbon::parse($consult->consultation_date)->format('Y-m-d');
            $status = $statuses[array_rand($statuses)];

            $year = date('Y', strtotime($reqDate));
            $reqNumber = sprintf('ANA-%s-%05d', $year, $count + 1);

            $testId = DB::table('lab_tests')->insertGetId([
                'request_number' => $reqNumber,
                'patient_id' => $consult->patient_id,
                'doctor_id' => $consult->doctor_id,
                'consultation_id' => $consultId,
                'requested_at' => $reqDate,
                'result_date' => $status === 'completed'
                    ? Carbon::parse($reqDate)->addDays(rand(1, 5))->format('Y-m-d') : null,
                'status' => $status,
                'laboratory_name' => $this->labos[array_rand($this->labos)],
                'clinical_info' => rand(0, 1) ? 'Bilan de contrôle. '.$consult->diagnosis : null,
                'doctor_interpretation' => $status === 'completed' && rand(0, 1)
                    ? 'Résultats dans les limites normales. Surveillance à poursuivre.' : null,
                'created_by' => $consult->doctor_id,
                'created_at' => $consult->consultation_date,
                'updated_at' => $consult->consultation_date,
            ]);
            $count++;

            // 3-6 résultats par analyse
            $nbResults = rand(3, 6);
            $usedTypes = [];
            for ($r = 0; $r < $nbResults; $r++) {
                $typeId = $this->labTestTypeIds[array_rand($this->labTestTypeIds)];
                if (in_array($typeId, $usedTypes)) {
                    continue;
                }
                $usedTypes[] = $typeId;

                $type = DB::table('lab_test_types')->where('id', $typeId)->first();
                $nMin = (float) ($type->normal_min ?? 0);
                $nMax = (float) ($type->normal_max ?? 100);

                // Générer une valeur : 80% normale, 20% hors normes
                if (rand(0, 9) < 8) {
                    $value = round($nMin + ($nMax - $nMin) * (rand(10, 90) / 100), 2);
                    $flag = 'normal';
                } elseif (rand(0, 1)) {
                    $value = round($nMin * rand(50, 85) / 100, 2);
                    $flag = $value < $nMin * 0.7 ? 'critical' : 'low';
                } else {
                    $value = round($nMax * rand(115, 180) / 100, 2);
                    $flag = $value > $nMax * 1.5 ? 'critical' : 'high';
                }

                DB::table('lab_test_results')->insert([
                    'lab_test_id' => $testId,
                    'lab_test_type_id' => $typeId,
                    'parameter_name' => $type->name,
                    'value' => (string) $value,
                    'unit' => $type->unit,
                    'normal_min' => $type->normal_min,
                    'normal_max' => $type->normal_max,
                    'flag' => $flag,
                    'notes' => null,
                    'created_at' => $consult->consultation_date,
                    'updated_at' => $consult->consultation_date,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedMedicalImages(): void
    {
        $categories = ['chest_xray', 'abdominal_xray', 'limb_xray', 'ct_scan', 'mri', 'ultrasound', 'other'];
        $bodyParts = ['Thorax', 'Abdomen', 'Genou gauche', 'Genou droit', 'Épaule gauche', 'Colonne lombaire', 'Hanche', 'Bassin', 'Poignet', 'Cheville'];
        $titles = [
            'chest_xray' => 'Radiographie thorax de face',
            'abdominal_xray' => 'Radiographie abdomen sans préparation',
            'limb_xray' => 'Radiographie membre',
            'ct_scan' => 'Scanner TDM',
            'mri' => 'IRM cérébrale',
            'ultrasound' => 'Échographie abdominale',
            'other' => 'Imagerie médicale',
        ];
        $reports = [
            'Pas d\'anomalie radiologique décelable. Parenchyme pulmonaire clair.',
            'Opacité basale droite évocatrice d\'un foyer infectieux.',
            'Discret épanchement pleural gauche.',
            'Aspect normal pour l\'âge du patient.',
            'Calcifications vasculaires en rapport avec l\'âge.',
        ];

        $patientsToImage = array_rand(array_flip($this->patientIds), min(40, count($this->patientIds)));
        foreach ((array) $patientsToImage as $patientId) {
            $nbImages = rand(1, 3);
            for ($i = 0; $i < $nbImages; $i++) {
                $cat = $categories[array_rand($categories)];
                $daysAgo = rand(1, 365);
                $imageDate = Carbon::now()->subDays($daysAgo)->format('Y-m-d');
                $doctorId = $this->doctorIds[array_rand($this->doctorIds)];

                DB::table('medical_images')->insert([
                    'patient_id' => $patientId,
                    'doctor_id' => $doctorId,
                    'consultation_id' => null,
                    'category' => $cat,
                    'title' => $titles[$cat].' — '.Carbon::parse($imageDate)->format('m/Y'),
                    'description' => rand(0, 1) ? 'Image de contrôle.' : null,
                    'file_path' => 'medical-images/demo-placeholder.jpg',
                    'file_type' => 'jpg',
                    'file_size' => rand(200000, 5000000),
                    'radiological_report' => rand(0, 1) ? $reports[array_rand($reports)] : null,
                    'image_date' => $imageDate,
                    'body_part' => rand(0, 1) ? $bodyParts[array_rand($bodyParts)] : null,
                    'created_by' => $doctorId,
                    'created_at' => $imageDate,
                    'updated_at' => $imageDate,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedDocuments(): void
    {
        $categories = array_keys(Document::CATEGORY_LABELS);
        $sources = ['Hôpital Ibn Sina', 'CHU Mohammed VI', 'Clinique Atlas', 'Polyclinique du Nord', 'Dr. externe', 'Patient lui-même'];

        $patientsForDoc = array_rand(array_flip($this->patientIds), min(35, count($this->patientIds)));
        foreach ((array) $patientsForDoc as $patientId) {
            $nbDocs = rand(1, 3);
            for ($i = 0; $i < $nbDocs; $i++) {
                $cat = $categories[array_rand($categories)];
                $daysAgo = rand(1, 500);
                $docDate = Carbon::now()->subDays($daysAgo)->format('Y-m-d');
                $doctorId = $this->doctorIds[array_rand($this->doctorIds)];
                $labelCat = Document::CATEGORY_LABELS[$cat];

                DB::table('documents')->insert([
                    'patient_id' => $patientId,
                    'consultation_id' => null,
                    'category' => $cat,
                    'title' => $labelCat.' — '.Carbon::parse($docDate)->format('d/m/Y'),
                    'description' => rand(0, 1) ? 'Document reçu du confrère / établissement.' : null,
                    'file_path' => 'documents/demo-placeholder.pdf',
                    'file_name' => 'document_'.$patientId.'_'.$i.'.pdf',
                    'file_type' => 'pdf',
                    'file_size' => rand(50000, 2000000),
                    'document_date' => $docDate,
                    'source' => rand(0, 1) ? $sources[array_rand($sources)] : null,
                    'uploaded_by' => $doctorId,
                    'created_at' => $docDate,
                    'updated_at' => $docDate,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedMedicalReports(): void
    {
        $types = array_keys(MedicalReport::TYPE_LABELS);
        $titles = [
            'fitness_certificate' => 'Certificat d\'aptitude physique',
            'sick_leave' => 'Arrêt de travail',
            'medical_report' => 'Compte-rendu de consultation',
            'referral' => 'Lettre de référence spécialiste',
            'other' => 'Document médical',
        ];

        $count = 0;
        foreach ($this->consultationIds as $consultId) {
            if ($count >= 40) {
                break;
            }
            if (rand(0, 9) >= 2) {
                continue;
            } // ~20%

            $consult = DB::table('consultations')->where('id', $consultId)->first();
            $type = $types[array_rand($types)];
            $reportDate = Carbon::parse($consult->consultation_date)->format('Y-m-d');
            $year = date('Y', strtotime($reportDate));
            $reportNum = sprintf('RPT-%s-%05d', $year, $count + 1);

            DB::table('medical_reports')->insert([
                'report_number' => $reportNum,
                'patient_id' => $consult->patient_id,
                'doctor_id' => $consult->doctor_id,
                'consultation_id' => $consultId,
                'type' => $type,
                'title' => $titles[$type],
                'content' => $this->generateReportContent($type, $consult),
                'report_date' => $reportDate,
                'valid_from' => $type === 'sick_leave' ? $reportDate : null,
                'valid_until' => $type === 'sick_leave'
                    ? Carbon::parse($reportDate)->addDays(rand(3, 14))->format('Y-m-d') : null,
                'sick_leave_days' => $type === 'sick_leave' ? rand(3, 14) : null,
                'is_printed' => rand(0, 1),
                'created_by' => $consult->doctor_id,
                'created_at' => $consult->consultation_date,
                'updated_at' => $consult->consultation_date,
            ]);
            $count++;
        }
    }

    private function generateReportContent(string $type, object $consult): string
    {
        $patient = DB::table('patients')->where('id', $consult->patient_id)->first();
        $name = $patient ? ($patient->first_name.' '.$patient->last_name) : 'le/la patient(e)';

        return match ($type) {
            'sick_leave' => "Je soussigné, certifie avoir examiné ce jour M./Mme {$name}.\nDiagnostic : {$consult->diagnosis}\nAu vu de son état de santé, je lui prescris un arrêt de travail.",
            'fitness_certificate' => "Je soussigné, certifie avoir examiné ce jour M./Mme {$name}.\nL'examen clinique est satisfaisant. Je le/la déclare apte à la pratique de ses activités habituelles.",
            'referral' => "Confrère/Consœur,\nJ'ai l'honneur de vous adresser M./Mme {$name} pour avis et prise en charge.\nDiagnostic : {$consult->diagnosis}\nCordialement.",
            default => "Compte-rendu de consultation de M./Mme {$name}.\nMotif : {$consult->reason}\nDiagnostic : {$consult->diagnosis}\nTraitement : {$consult->treatment_plan}",
        };
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedPayments(): void
    {
        if (empty($this->medicalActIds)) {
            $this->command->warn('Aucun acte médical trouvé — paiements simplifiés.');
        }

        $statuses = ['paid', 'paid', 'paid', 'pending', 'partial', 'cancelled'];
        $methods = ['cash', 'cash', 'bank_transfer', 'insurance', 'mixed'];
        $count = 0;

        foreach ($this->consultationIds as $consultId) {
            if (rand(0, 9) >= 7) {
                continue;
            } // ~70%

            $consult = DB::table('consultations')->where('id', $consultId)->first();
            $payDate = Carbon::parse($consult->consultation_date)->format('Y-m-d');
            $status = $statuses[array_rand($statuses)];
            $method = $status !== 'pending' ? $methods[array_rand($methods)] : null;

            $year = date('Y', strtotime($payDate));
            $invoiceNum = sprintf('FAC-%s-%05d', $year, $count + 1);

            // Lignes de paiement (1-3 actes)
            $lines = [];
            $subtotal = 0;
            $nbActs = rand(1, 3);

            for ($l = 0; $l < $nbActs; $l++) {
                if (! empty($this->medicalActIds)) {
                    $actId = $this->medicalActIds[array_rand($this->medicalActIds)];
                    $act = DB::table('medical_acts')->where('id', $actId)->first();
                    $desc = $act->name;
                    $price = (float) $act->price;
                    $actIdForLine = $actId;
                } else {
                    $desc = ['Consultation', 'Acte médical', 'Soins infirmiers'][rand(0, 2)];
                    $price = [200, 300, 400, 500, 600][rand(0, 4)];
                    $actIdForLine = null;
                }
                $qty = 1;
                $lines[] = ['act_id' => $actIdForLine, 'desc' => $desc, 'price' => $price, 'qty' => $qty];
                $subtotal += $price * $qty;
            }

            $discount = rand(0, 4) === 0 ? round($subtotal * rand(5, 20) / 100, 2) : 0;
            $insurance = rand(0, 3) === 0 ? round($subtotal * rand(10, 70) / 100, 2) : 0;
            $total = round($subtotal - $discount, 2);
            $amountDue = round($total - $insurance, 2);

            if ($status === 'paid') {
                $amountPaid = $amountDue;
                $remaining = 0;
            } elseif ($status === 'partial') {
                $amountPaid = round($amountDue * rand(30, 70) / 100, 2);
                $remaining = round($amountDue - $amountPaid, 2);
            } else {
                $amountPaid = 0;
                $remaining = $amountDue;
            }

            $payId = DB::table('payments')->insertGetId([
                'invoice_number' => $invoiceNum,
                'patient_id' => $consult->patient_id,
                'doctor_id' => $consult->doctor_id,
                'consultation_id' => $consultId,
                'status' => $status,
                'payment_method' => $method,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'insurance_coverage' => $insurance,
                'total_amount' => $total,
                'amount_paid' => $amountPaid,
                'amount_due' => $remaining,
                'payment_date' => in_array($status, ['paid', 'partial']) ? $payDate : null,
                'due_date' => $status === 'pending'
                    ? Carbon::parse($payDate)->addDays(30)->format('Y-m-d') : null,
                'notes' => null,
                'created_by' => $consult->doctor_id,
                'created_at' => $consult->consultation_date,
                'updated_at' => $consult->consultation_date,
            ]);
            $count++;

            foreach ($lines as $line) {
                DB::table('payment_lines')->insert([
                    'payment_id' => $payId,
                    'medical_act_id' => $line['act_id'],
                    'description' => $line['desc'],
                    'quantity' => $line['qty'],
                    'unit_price' => $line['price'],
                    'total_price' => $line['price'] * $line['qty'],
                    'created_at' => $consult->consultation_date,
                    'updated_at' => $consult->consultation_date,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function seedMedicalHistories(): void
    {
        $antecedentsMed = [
            'Hypertension artérielle', 'Diabète de type 2', 'Asthme bronchique',
            'Insuffisance cardiaque', 'Cardiopathie ischémique', 'Dyslipidémie',
            'Hypothyroïdie', 'Ulcère gastrique', 'Hépatite virale B', 'Épilepsie',
        ];
        $antecedentsFam = [
            'HTA paternelle', 'Diabète maternel', 'Cardiopathie familiale',
            'Cancer colorectal familial', 'Diabète familial',
        ];
        $allergies = [
            'Allergie à la Pénicilline', 'Allergie à l\'Aspirine', 'Allergie aux AINS',
            'Allergie aux Sulfamides', 'Allergie au Latex',
        ];
        $chirurgicaux = [
            'Appendicectomie', 'Cholécystectomie', 'Cure hernie inguinale',
            'Césarienne', 'Amygdalectomie',
        ];

        foreach ($this->patientIds as $patientId) {
            $doctorId = $this->doctorIds[array_rand($this->doctorIds)];

            // Antécédents médicaux (0-3)
            $nbMed = rand(0, 3);
            $used = [];
            for ($i = 0; $i < $nbMed; $i++) {
                $ante = $antecedentsMed[array_rand($antecedentsMed)];
                if (in_array($ante, $used)) {
                    continue;
                }
                $used[] = $ante;
                DB::table('medical_histories')->insert([
                    'patient_id' => $patientId,
                    'type' => 'medical',
                    'title' => $ante,
                    'description' => 'Diagnostiqué il y a '.rand(1, 20).' ans. Suivi régulier.',
                    'date_occurred' => Carbon::now()->subYears(rand(1, 20))->format('Y-m-d'),
                    'created_by' => $doctorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Antécédents familiaux
            if (rand(0, 1)) {
                DB::table('medical_histories')->insert([
                    'patient_id' => $patientId,
                    'type' => 'family',
                    'title' => $antecedentsFam[array_rand($antecedentsFam)],
                    'description' => null,
                    'date_occurred' => null,
                    'created_by' => $doctorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Antécédents chirurgicaux
            if (rand(0, 3) === 0) {
                $year = rand(2000, 2023);
                DB::table('medical_histories')->insert([
                    'patient_id' => $patientId,
                    'type' => 'surgical',
                    'title' => $chirurgicaux[array_rand($chirurgicaux)],
                    'description' => "Intervention réalisée en $year. Suites simples.",
                    'date_occurred' => Carbon::create($year, rand(1, 12), rand(1, 28))->format('Y-m-d'),
                    'created_by' => $doctorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Allergies
            if (rand(0, 3) === 0) {
                DB::table('medical_histories')->insert([
                    'patient_id' => $patientId,
                    'type' => 'allergy',
                    'title' => $allergies[array_rand($allergies)],
                    'description' => 'Réaction documentée. À éviter.',
                    'date_occurred' => null,
                    'created_by' => $doctorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Habitudes (tabac, etc.) - ~30% des patients
            if (rand(0, 2) === 0) {
                DB::table('medical_histories')->insert([
                    'patient_id' => $patientId,
                    'type' => 'habit',
                    'title' => 'Tabagisme',
                    'description' => rand(0, 1) ? 'Fumeur actif' : 'Sevrage tabagique',
                    'habit_type' => 'smoking',
                    'habit_frequency' => rand(5, 20).' cigarettes/jour',
                    'date_occurred' => null,
                    'created_by' => $doctorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
