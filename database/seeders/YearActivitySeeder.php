<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Génère une activité réaliste du cabinet pour l'année 2026 :
 *  - patients, consultations, ordonnances, analyses, paiements… du 1er janvier à aujourd'hui
 *  - rendez-vous du 1er janvier au 31 décembre (les futurs sont planifiés / confirmés)
 *
 * Les données existantes sont conservées (ajout uniquement).
 * Lancement : php artisan db:seed --class=YearActivitySeeder
 */
class YearActivitySeeder extends Seeder
{
    private const YEAR = 2026;

    private const NEW_PATIENTS = 220;

    private Carbon $now;

    private array $doctors = [];          // id => speciality

    private ?int $secretaryId = null;

    private array $patients = [];         // id => profil (created_at, chronic, insurance, poids, taille)

    private array $pendingNewPatients = []; // patients créés mais sans premier RDV

    private array $medications = [];

    private array $acts = [];             // code => act

    private array $labTypes = [];

    private array $counters = [];

    private array $usedCins = [];

    private array $stats = [];

    // Lignes enfant insérées en masse
    private array $buffer = ['prescription_lines' => [], 'lab_test_results' => [], 'payment_lines' => []];

    // ── Référentiels ──────────────────────────────────────────────────
    private array $maleNames = ['Mohamed', 'Ahmed', 'Youssef', 'Khalid', 'Hassan', 'Abdellatif', 'Rachid', 'Said', 'Omar', 'Ali', 'Hamid', 'Karim', 'Nabil', 'Jamal', 'Fouad', 'Mustapha', 'Tarik', 'Mehdi', 'Soufiane', 'Ilyass', 'Amine', 'Bilal', 'Zakaria', 'Othmane', 'Ayoub', 'Hicham', 'Adil', 'Driss', 'Abdelkader', 'Anas', 'Reda', 'Hamza'];

    private array $femaleNames = ['Fatima', 'Aicha', 'Khadija', 'Zineb', 'Leila', 'Nadia', 'Samira', 'Hafida', 'Meryem', 'Soumia', 'Houda', 'Amina', 'Sanaa', 'Siham', 'Wafaa', 'Naima', 'Loubna', 'Sara', 'Hind', 'Imane', 'Ghita', 'Rajaa', 'Bouchra', 'Kawtar', 'Salma', 'Yasmine', 'Malika', 'Hanane', 'Asmae', 'Nora'];

    private array $lastNames = ['Benali', 'El Amrani', 'Berrada', 'Tazi', 'Fassi', 'Alaoui', 'Chraibi', 'Sefrioui', 'Kettani', 'Squalli', 'Filali', 'Bennani', 'Benaissa', 'Tahiri', 'Ouazzani', 'Bensouda', 'Lahlou', 'Belcaid', 'Idrissi', 'Boukhrissi', 'Charaf', 'El Hajji', 'Guerraoui', 'Haddad', 'Ziane', 'El Mansouri', 'Benkirane', 'Saidi', 'Raji', 'Amrani', 'El Idrissi', 'Ait Ouarab', 'Bouzidi', 'Naciri', 'Skalli', 'Jaidi', 'Mernissi', 'Zemmouri', 'Lamrani', 'Hajjaji'];

    private array $cities = ['Casablanca', 'Casablanca', 'Casablanca', 'Mohammedia', 'Rabat', 'Salé', 'Berrechid', 'Settat', 'El Jadida', 'Bouskoura', 'Marrakech', 'Fès'];

    private array $streets = ['Bd Zerktouni', 'Bd Anfa', 'Rue Ibn Batouta', 'Av. Hassan II', 'Bd Mohammed V', 'Rue Moulay Youssef', 'Bd Ghandi', 'Av. des FAR', 'Rue Allal Ben Abdellah', 'Bd Abdelmoumen', 'Rue Oued Sebou', 'Bd Al Massira'];

    private array $professions = ['Enseignant', 'Fonctionnaire', 'Commerçant', 'Ingénieur', 'Retraité', 'Étudiant', 'Artisan', 'Sans profession', 'Infirmier', 'Comptable', 'Avocat', 'Chauffeur', 'Employé de banque', 'Technicien', 'Femme au foyer', 'Cadre commercial'];

    private array $insurances = [null, null, null, 'CNOPS', 'CNOPS', 'CNSS (AMO)', 'CNSS (AMO)', 'CNSS (AMO)', 'AMO Tadamon', 'FAR', 'Allianz Maroc', 'AXA Assurance', 'Saham Assurance', 'RMA Watanya', 'Wafa Assurance'];

    private array $bloodGroups = ['O+', 'O+', 'O+', 'A+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-'];

    private array $labs = ['Laboratoire Ibn Sina', 'Laboratoire Atlas', 'Laboratoire Al Andalous', 'Biochimie Centrale', 'Laboratoire du Centre', 'Labotest Maroc'];

    private array $cancelReasons = ['Empêchement personnel', 'Patient malade', 'Déplacement professionnel', 'Rendez-vous reporté par le patient', 'Indisponibilité du médecin', 'Problème de transport'];

    private array $typeColors = ['general' => '#01a9ac', 'specialized' => '#3498db', 'followup' => '#2ecc71', 'emergency' => '#e74c3c', 'control' => '#f39c12', 'teleconsultation' => '#9b59b6'];

    /**
     * Pathologies : motif, CIM-10, médicaments (ids), bilans (ids types analyses).
     */
    private array $conditions = [
        'hta' => ['diag' => 'Hypertension artérielle essentielle', 'icd' => 'I10', 'reasons' => ['Suivi hypertension', 'Contrôle tension artérielle', 'Céphalées et vertiges'], 'meds' => [8, 9, 14], 'labs' => [10, 11, 17, 18], 'chronic' => true],
        'diabete' => ['diag' => 'Diabète de type 2', 'icd' => 'E11', 'reasons' => ['Suivi diabète', 'Polyurie et soif intense', 'Contrôle glycémie'], 'meds' => [7, 10], 'labs' => [8, 9, 10, 13, 16], 'chronic' => true],
        'dyslip' => ['diag' => 'Dyslipidémie', 'icd' => 'E78.5', 'reasons' => ['Bilan lipidique perturbé', 'Contrôle cholestérol'], 'meds' => [10], 'labs' => [13, 14, 15, 16, 21, 22], 'chronic' => true],
        'thyro' => ['diag' => 'Hypothyroïdie', 'icd' => 'E03.9', 'reasons' => ['Fatigue générale', 'Suivi thyroïde', 'Prise de poids inexpliquée'], 'meds' => [13], 'labs' => [27, 28], 'chronic' => true],
        'asthme' => ['diag' => 'Asthme bronchique', 'icd' => 'J45.9', 'reasons' => ['Dyspnée sifflante', 'Suivi asthme', 'Toux nocturne'], 'meds' => [11, 12], 'labs' => [1, 2, 4], 'chronic' => true],
        'ivrs' => ['diag' => 'Infection des voies respiratoires supérieures', 'icd' => 'J06.9', 'reasons' => ['Fièvre et mal de gorge', 'Rhinorrhée et obstruction nasale', 'Toux et fièvre'], 'meds' => [1, 4, 2], 'labs' => [1, 4, 30], 'chronic' => false],
        'bronchite' => ['diag' => 'Bronchite aiguë', 'icd' => 'J20.9', 'reasons' => ['Toux productive', 'Toux et dyspnée'], 'meds' => [5, 1, 11], 'labs' => [4, 30, 31], 'chronic' => false],
        'sinusite' => ['diag' => 'Sinusite aiguë', 'icd' => 'J01.9', 'reasons' => ['Céphalées frontales', 'Douleurs faciales et écoulement nasal'], 'meds' => [5, 2, 3], 'labs' => [4, 30], 'chronic' => false],
        'gastrite' => ['diag' => 'Gastrite chronique', 'icd' => 'K29.5', 'reasons' => ['Douleurs épigastriques', 'Brûlures d\'estomac'], 'meds' => [6], 'labs' => [1, 2, 21, 22], 'chronic' => false],
        'rgo' => ['diag' => 'Reflux gastro-œsophagien', 'icd' => 'K21.0', 'reasons' => ['Pyrosis', 'Régurgitations acides'], 'meds' => [6], 'labs' => [], 'chronic' => false],
        'lombalgie' => ['diag' => 'Lombalgie commune', 'icd' => 'M54.5', 'reasons' => ['Douleurs lombaires', 'Lumbago aigu'], 'meds' => [3, 1, 12], 'labs' => [30, 31], 'chronic' => false],
        'arthrose' => ['diag' => 'Arthrose du genou', 'icd' => 'M17', 'reasons' => ['Douleurs articulaires', 'Douleur du genou à la marche'], 'meds' => [1, 3], 'labs' => [30, 31, 12], 'chronic' => false],
        'migraine' => ['diag' => 'Migraine sans aura', 'icd' => 'G43.0', 'reasons' => ['Céphalées récurrentes', 'Céphalées pulsatiles'], 'meds' => [3, 1], 'labs' => [], 'chronic' => false],
        'anemie' => ['diag' => 'Anémie ferriprive', 'icd' => 'D50.9', 'reasons' => ['Fatigue générale', 'Pâleur et essoufflement'], 'meds' => [], 'labs' => [1, 2, 3, 6, 7], 'chronic' => false],
        'anxiete' => ['diag' => 'Anxiété généralisée', 'icd' => 'F41.1', 'reasons' => ['Troubles du sommeil', 'Palpitations et stress'], 'meds' => [15], 'labs' => [27], 'chronic' => false],
        'uti' => ['diag' => 'Infection urinaire basse', 'icd' => 'N39.0', 'reasons' => ['Brûlures mictionnelles', 'Pollakiurie'], 'meds' => [4, 1], 'labs' => [33, 4, 30], 'chronic' => false],
        'gastro' => ['diag' => 'Gastro-entérite aiguë', 'icd' => 'A09', 'reasons' => ['Diarrhée et vomissements', 'Douleurs abdominales'], 'meds' => [2], 'labs' => [17, 18, 1], 'chronic' => false],
        'bilan' => ['diag' => 'Examen de santé général — normal', 'icd' => 'Z00.0', 'reasons' => ['Bilan de santé annuel', 'Certificat médical', 'Visite de contrôle'], 'meds' => [], 'labs' => [1, 2, 4, 5, 8, 10, 13, 16], 'chronic' => false],
        'palpit' => ['diag' => 'Palpitations — extrasystoles bénignes', 'icd' => 'R00.2', 'reasons' => ['Palpitations cardiaques', 'Douleur thoracique atypique'], 'meds' => [14], 'labs' => [17, 18, 27], 'chronic' => false],
    ];

    private array $frequencies = ['1 fois/jour', '2 fois/jour', '3 fois/jour', 'Matin et soir', 'Matin, midi et soir', 'Au besoin'];

    // Jours fériés 2026 (Maroc, dates religieuses approximatives)
    private array $holidays = [
        '2026-01-01', '2026-01-11', '2026-01-14', '2026-03-20', '2026-03-21', '2026-05-01',
        '2026-05-27', '2026-05-28', '2026-06-17', '2026-07-30', '2026-08-14', '2026-08-20',
        '2026-08-21', '2026-08-26', '2026-11-06', '2026-11-18',
    ];

    // ═════════════════════════════════════════════════════════════════
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Données fictives refusées en production.');

            return;
        }

        mt_srand(self::YEAR);
        $this->now = Carbon::now();

        $this->loadReferences();
        if (empty($this->doctors)) {
            $this->command->error('Aucun médecin trouvé. Lancez d\'abord les seeders de base.');

            return;
        }

        $this->command->info('Création de '.self::NEW_PATIENTS.' nouveaux patients (janvier → aujourd\'hui)...');
        $newPatients = $this->seedPatients();

        $start = Carbon::create(self::YEAR, 1, 1);
        $end = Carbon::create(self::YEAR, 12, 31);
        $currentMonth = null;
        $patientCursor = 0;

        foreach (CarbonPeriod::create($start, $end) as $day) {
            if ($currentMonth !== $day->month) {
                if ($currentMonth !== null) {
                    $this->flushBuffers();
                    DB::commit();
                }
                $currentMonth = $day->month;
                $this->command->info('  → '.ucfirst($day->locale('fr')->translatedFormat('F Y')));
                DB::beginTransaction();
            }

            // Les nouveaux patients rejoignent la patientèle à leur date de création
            while ($patientCursor < count($newPatients) && $newPatients[$patientCursor]['created_at']->lte($day->copy()->endOfDay())) {
                $p = $newPatients[$patientCursor++];
                $this->patients[$p['id']] = $p;
                $this->pendingNewPatients[] = $p['id'];
            }

            $this->seedDay($day->copy());
        }

        $this->flushBuffers();
        DB::commit();

        $this->command->info('Création des antécédents des nouveaux patients...');
        $this->seedMedicalHistories($newPatients);

        $this->command->newLine();
        $this->command->info('Terminé !');
        $this->command->table(['Table', 'Ajoutés'], collect($this->stats)->map(fn ($v, $k) => [$k, $v])->values()->all());
    }

    // ─────────────────────────────────────────────────────────────────
    private function loadReferences(): void
    {
        $this->doctors = DB::table('users')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'medecin')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->pluck('users.speciality', 'users.id')
            ->all();

        $this->secretaryId = DB::table('users')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'secretaire')
            ->value('users.id');

        $this->medications = DB::table('medications')->where('is_active', true)->get()->keyBy('id')->all();
        $this->acts = DB::table('medical_acts')->where('is_active', true)->get()->keyBy('code')->all();
        $this->labTypes = DB::table('lab_test_types')->where('is_active', true)->get()->keyBy('id')->all();
        $this->usedCins = array_flip(DB::table('patients')->whereNotNull('cin')->pluck('cin')->all());

        // Patients existants (créés avant 2026 ou déjà présents)
        foreach (DB::table('patients')->whereNull('deleted_at')->get() as $p) {
            $this->patients[$p->id] = $this->profile($p->id, Carbon::parse($p->created_at), $p->insurance_name, $p->gender, $p->birth_date);
        }
    }

    private function profile(int $id, Carbon $createdAt, ?string $insurance, string $gender, string $birthDate): array
    {
        $age = Carbon::parse($birthDate)->age;
        $chronic = null;
        if ($age > 40 && $this->chance(45)) {
            $chronic = $this->pick(['hta', 'hta', 'diabete', 'diabete', 'dyslip', 'thyro']);
        } elseif ($this->chance(10)) {
            $chronic = $this->pick(['asthme', 'thyro']);
        }

        return [
            'id' => $id,
            'created_at' => $createdAt,
            'insurance' => $insurance,
            'chronic' => $chronic,
            'weight' => $gender === 'male' ? mt_rand(62, 98) : mt_rand(50, 85),
            'height' => $gender === 'male' ? mt_rand(165, 188) : mt_rand(153, 172),
            'age' => $age,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    private function seedPatients(): array
    {
        $created = [];
        $start = Carbon::create(self::YEAR, 1, 2)->timestamp;
        $end = $this->now->timestamp;

        $dates = [];
        for ($i = 0; $i < self::NEW_PATIENTS; $i++) {
            $dates[] = mt_rand($start, $end);
        }
        sort($dates);

        foreach ($dates as $i => $ts) {
            $createdAt = Carbon::createFromTimestamp($ts)->setTime(mt_rand(8, 17), mt_rand(0, 59));
            if ($createdAt->gt($this->now)) {
                $createdAt = $this->now->copy()->subMinutes(30);
            }
            $isMale = $this->chance(48);
            $first = $isMale ? $this->pick($this->maleNames) : $this->pick($this->femaleNames);
            $last = $this->pick($this->lastNames);
            $birth = Carbon::create(mt_rand(1945, 2008), mt_rand(1, 12), mt_rand(1, 28));
            if ($this->chance(8)) {
                $birth = Carbon::create(mt_rand(2012, 2022), mt_rand(1, 12), mt_rand(1, 28)); // enfants
            }
            $insurance = $this->pick($this->insurances);
            $isAdult = $birth->age >= 18;

            $id = DB::table('patients')->insertGetId([
                'dossier_number' => $this->nextNumber('patients', 'dossier_number', 'DOS'),
                'first_name' => $first,
                'last_name' => $last,
                'cin' => $isAdult ? $this->uniqueCin() : null,
                'birth_date' => $birth->format('Y-m-d'),
                'gender' => $isMale ? 'male' : 'female',
                'phone' => $this->pick(['06', '07']).mt_rand(10000000, 99999999),
                'phone_secondary' => $this->chance(25) ? '05'.mt_rand(22000000, 22999999) : null,
                'email' => $isAdult && $this->chance(60) ? Str::slug($first.'.'.$last, '.').mt_rand(1, 99).'@'.$this->pick(['gmail.com', 'gmail.com', 'hotmail.com', 'yahoo.fr', 'outlook.com']) : null,
                'address' => mt_rand(1, 250).', '.$this->pick($this->streets),
                'city' => $this->pick($this->cities),
                'blood_group' => $this->chance(60) ? $this->pick($this->bloodGroups) : null,
                'known_allergies' => $this->chance(12) ? $this->pick(['Pénicilline', 'Aspirine', 'AINS', 'Sulfamides', 'Pollen']) : null,
                'profession' => $isAdult ? $this->pick($this->professions) : 'Élève',
                'insurance_name' => $insurance,
                'insurance_number' => $insurance ? strtoupper(Str::random(3)).mt_rand(100000, 999999) : null,
                'is_active' => true,
                'created_by' => $this->secretaryId ?? array_key_first($this->doctors),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $created[] = $this->profile($id, $createdAt, $insurance, $isMale ? 'male' : 'female', $birth->format('Y-m-d'));
        }
        $this->stat('Patients', count($created));

        return $created;
    }

    // ─────────────────────────────────────────────────────────────────
    private function seedDay(Carbon $day): void
    {
        if ($day->isSunday() || in_array($day->toDateString(), $this->holidays, true)) {
            return;
        }

        $isSaturday = $day->isSaturday();
        $morning = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30'];
        $afternoon = ['14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00'];
        $slots = $isSaturday ? $morning : array_merge($morning, $afternoon);

        // Activité plus faible en août et pendant le Ramadan, plus forte en hiver (grippe)
        $factor = match (true) {
            $day->month === 8 => 0.6,
            $day->between(Carbon::create(self::YEAR, 2, 18), Carbon::create(self::YEAR, 3, 19)) => 0.75,
            in_array($day->month, [1, 2, 11, 12], true) => 1.15,
            default => 1.0,
        };

        foreach ($this->doctors as $doctorId => $speciality) {
            if ($this->onVacation($doctorId, $day)) {
                continue;
            }

            $count = $isSaturday ? mt_rand(2, 5) : mt_rand(4, 10);
            $count = max(1, min(count($slots), (int) round($count * $factor)));

            $chosen = (array) array_rand(array_flip($slots), $count);
            sort($chosen);

            foreach ($chosen as $slot) {
                [$h, $m] = explode(':', $slot);
                $this->seedAppointment($day->copy()->setTime((int) $h, (int) $m), $doctorId, $speciality);
            }
        }
    }

    private function onVacation(int $doctorId, Carbon $day): bool
    {
        // Deux semaines de congés par médecin, décalées
        $index = array_search($doctorId, array_keys($this->doctors), true);
        $start = Carbon::create(self::YEAR, 7, 13)->addWeeks($index * 2);

        return $day->between($start, $start->copy()->addDays(13));
    }

    // ─────────────────────────────────────────────────────────────────
    private function seedAppointment(Carbon $scheduledAt, int $doctorId, ?string $speciality): void
    {
        $patient = $this->choosePatient($scheduledAt);
        if (! $patient) {
            return;
        }

        $isCardio = $speciality && str_contains(Str::lower($speciality), 'cardio');
        $conditionKey = $this->chooseCondition($patient, $isCardio);
        $condition = $this->conditions[$conditionKey];

        $type = match (true) {
            $this->chance(4) => 'emergency',
            $this->chance(3) => 'teleconsultation',
            $condition['chronic'] && $this->chance(60) => 'followup',
            $isCardio => $this->pick(['specialized', 'specialized', 'control']),
            $this->chance(12) => 'control',
            default => 'general',
        };

        $duration = $this->pick([15, 20, 30, 30, 30]);
        $status = $this->appointmentStatus($scheduledAt);
        $isPast = in_array($status, ['completed', 'waiting', 'in_progress', 'no_show'], true)
            || ($status === 'cancelled' && $scheduledAt->lt($this->now));

        $createdAt = $scheduledAt->copy()->subDays(mt_rand(0, 21))->setTime(mt_rand(8, 18), mt_rand(0, 59));
        if ($createdAt->lt($patient['created_at'])) {
            $createdAt = $patient['created_at']->copy();
        }
        if ($createdAt->gt($scheduledAt)) {
            $createdAt = $scheduledAt->copy()->subMinutes(mt_rand(20, 90));
        }
        if ($createdAt->gt($this->now)) {
            $createdAt = $this->now->copy()->subMinutes(mt_rand(5, 60));
        }

        $arrivedAt = in_array($status, ['completed', 'waiting', 'in_progress'], true) ? $scheduledAt->copy()->addMinutes(mt_rand(-15, 10)) : null;
        $startedAt = in_array($status, ['completed', 'in_progress'], true) ? $scheduledAt->copy()->addMinutes(mt_rand(0, 25)) : null;
        $endedAt = $status === 'completed' ? $startedAt->copy()->addMinutes($duration + mt_rand(-5, 10)) : null;

        $appointmentId = DB::table('appointments')->insertGetId([
            'patient_id' => $patient['id'],
            'doctor_id' => $doctorId,
            'created_by' => $this->chance(80) && $this->secretaryId ? $this->secretaryId : $doctorId,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $duration,
            'consultation_type' => $type,
            'status' => $status,
            'cancellation_reason' => $status === 'cancelled' ? $this->pick($this->cancelReasons) : null,
            'notes' => $this->chance(35) ? $this->pick($condition['reasons']) : null,
            'color' => $this->typeColors[$type],
            'reminder_sent' => $isPast || $scheduledAt->lte($this->now->copy()->addDay()) ? $this->chance(85) : false,
            'arrived_at' => $arrivedAt,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'created_at' => $createdAt,
            'updated_at' => $endedAt ?? ($isPast ? $scheduledAt : $createdAt),
        ]);
        $this->stat('Rendez-vous');
        $this->stat('  dont futurs (après aujourd\'hui)', $scheduledAt->gt($this->now) ? 1 : 0);

        if (in_array($status, ['completed', 'in_progress'], true)) {
            $this->seedConsultation($appointmentId, $patient, $doctorId, $type, $conditionKey, $startedAt, $endedAt, $status);
        }
    }

    private function appointmentStatus(Carbon $at): string
    {
        // Rendez-vous futurs
        if ($at->gt($this->now)) {
            if ($this->chance(6)) {
                return 'cancelled';
            }
            $daysAhead = $this->now->diffInDays($at);

            return $daysAhead <= 7 && $this->chance(65) ? 'confirmed' : ($this->chance(20) ? 'confirmed' : 'planned');
        }

        // Aujourd'hui, créneaux proches de l'heure actuelle
        $minutesAgo = $at->diffInMinutes($this->now);
        if ($at->isSameDay($this->now) && $minutesAgo < 60) {
            return $minutesAgo < 25 ? 'waiting' : 'in_progress';
        }

        // Passé
        $r = mt_rand(1, 100);

        return match (true) {
            $r <= 80 => 'completed',
            $r <= 90 => 'cancelled',
            default => 'no_show',
        };
    }

    private function choosePatient(Carbon $at): ?array
    {
        // Les nouveaux patients ont leur premier rendez-vous en priorité
        if (! empty($this->pendingNewPatients) && $at->lte($this->now)) {
            $id = array_shift($this->pendingNewPatients);

            return $this->patients[$id];
        }

        // Les patients chroniques reviennent plus souvent
        for ($try = 0; $try < 5; $try++) {
            $p = $this->patients[array_rand($this->patients)];
            if ($p['created_at']->gt($at)) {
                continue;
            }
            if ($p['chronic'] || $this->chance(70)) {
                return $p;
            }
        }

        return $this->patients[array_rand($this->patients)];
    }

    private function chooseCondition(array $patient, bool $isCardio): string
    {
        if ($patient['chronic'] && $this->chance(55)) {
            return $patient['chronic'];
        }
        if ($isCardio && $this->chance(60)) {
            return $this->pick(['hta', 'palpit', 'dyslip', 'bilan']);
        }

        $acute = array_keys(array_filter($this->conditions, fn ($c) => ! $c['chronic']));

        return $this->pick($acute);
    }

    // ─────────────────────────────────────────────────────────────────
    private function seedConsultation(int $appointmentId, array $patient, int $doctorId, string $type, string $conditionKey, Carbon $date, ?Carbon $endedAt, string $appointmentStatus): void
    {
        $c = $this->conditions[$conditionKey];
        $reason = $this->pick($c['reasons']);

        $systolic = $conditionKey === 'hta' ? mt_rand(135, 170) : mt_rand(105, 135);
        $diastolic = (int) round($systolic * 0.62 + mt_rand(-5, 5));
        $fever = in_array($conditionKey, ['ivrs', 'bronchite', 'sinusite', 'uti', 'gastro'], true);

        $followup = $c['chronic'] || $this->chance(15);
        $treatment = match (true) {
            empty($c['meds']) => 'Mesures hygiéno-diététiques. Bilan biologique demandé.',
            $c['chronic'] => 'Poursuite du traitement de fond. Régime adapté et activité physique régulière.',
            default => 'Traitement symptomatique prescrit. Repos et hydratation. Réévaluation si persistance.',
        };

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient['id'],
            'doctor_id' => $doctorId,
            'appointment_id' => $appointmentId,
            'consultation_type' => $type,
            'status' => $appointmentStatus === 'in_progress' ? 'in_progress' : 'completed',
            'consultation_date' => $date,
            'reason' => $reason,
            'symptoms' => 'Patient(e) se plaint de '.Str::lower($reason).' depuis '.mt_rand(1, 15).' jours.',
            'clinical_exam' => $this->pick([
                'Examen général satisfaisant. Auscultation cardio-pulmonaire normale. Abdomen souple.',
                'Bon état général. Pas de signe de gravité. Examen ORL : pharynx érythémateux.',
                'Patient conscient, coopérant. Auscultation pulmonaire : quelques râles bronchiques.',
                'Examen cardiovasculaire sans particularité. Pas d\'œdème des membres inférieurs.',
                'Abdomen sensible en épigastre, sans défense. Reste de l\'examen normal.',
                'Contracture paravertébrale lombaire. Lasègue négatif. Examen neurologique normal.',
            ]),
            'weight_kg' => $patient['age'] >= 14 ? round($patient['weight'] + mt_rand(-20, 20) / 10, 1) : mt_rand(20, 45),
            'height_cm' => $patient['age'] >= 14 ? $patient['height'] : mt_rand(110, 155),
            'blood_pressure' => $systolic.'/'.$diastolic,
            'heart_rate' => mt_rand(62, 98),
            'temperature' => $fever ? mt_rand(375, 393) / 10 : mt_rand(363, 373) / 10,
            'spo2' => $conditionKey === 'asthme' || $conditionKey === 'bronchite' ? mt_rand(93, 98) : mt_rand(96, 100),
            'diagnosis' => $c['diag'],
            'icd10_code' => $c['icd'],
            'treatment_plan' => $treatment,
            'doctor_notes' => $this->chance(25) ? $this->pick(['Patient coopératif. Bonne observance.', 'Éducation thérapeutique faite.', 'Revoir avec les résultats du bilan.', 'Conseils hygiéno-diététiques donnés.']) : null,
            'summary' => $c['diag'].'. '.$treatment,
            'followup_required' => $followup,
            'followup_date' => $followup ? $date->copy()->addDays($c['chronic'] ? mt_rand(30, 90) : mt_rand(7, 21))->format('Y-m-d') : null,
            'created_by' => $doctorId,
            'created_at' => $date,
            'updated_at' => $endedAt ?? $date,
        ]);
        $this->stat('Consultations');

        if ($appointmentStatus === 'in_progress') {
            return; // consultation en cours : pas encore d'ordonnance ni de facture
        }

        if (! empty($c['meds']) && $this->chance(85)) {
            $this->seedPrescription($consultationId, $patient, $doctorId, $c, $date);
        }
        if (! empty($c['labs']) && $this->chance($conditionKey === 'bilan' ? 90 : ($c['chronic'] ? 45 : 20))) {
            $this->seedLabTest($consultationId, $patient, $doctorId, $c, $date);
        }
        if ($this->chance(93)) {
            $this->seedPayment($consultationId, $patient, $doctorId, $type, $conditionKey, $date);
        }
        if ($this->chance(7)) {
            $this->seedMedicalReport($consultationId, $patient, $doctorId, $c, $conditionKey, $date);
        }
        if ($this->chance(4)) {
            $this->seedMedicalImage($consultationId, $patient, $doctorId, $conditionKey, $date);
        }
        if ($this->chance(4)) {
            $this->seedDocument($consultationId, $patient, $doctorId, $date);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    private function seedPrescription(int $consultationId, array $patient, int $doctorId, array $c, Carbon $date): void
    {
        $meds = array_values(array_filter($c['meds'], fn ($id) => isset($this->medications[$id])));
        if (empty($meds)) {
            return;
        }
        shuffle($meds);
        $meds = array_slice($meds, 0, mt_rand(1, min(3, count($meds))));
        if ($this->chance(30) && isset($this->medications[1]) && ! in_array(1, $meds, true)) {
            $meds[] = 1; // Paracétamol en complément
        }

        $prescriptionId = DB::table('prescriptions')->insertGetId([
            'prescription_number' => $this->nextNumber('prescriptions', 'prescription_number', 'ORD'),
            'patient_id' => $patient['id'],
            'doctor_id' => $doctorId,
            'consultation_id' => $consultationId,
            'prescribed_at' => $date->toDateString(),
            'valid_until' => $date->copy()->addDays($c['chronic'] ? 90 : 30)->toDateString(),
            'notes' => $this->chance(30) ? $this->pick(['À prendre avec les repas.', 'Éviter l\'automédication.', 'Ne pas interrompre le traitement sans avis médical.']) : null,
            'is_printed' => $this->chance(85),
            'created_by' => $doctorId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $this->stat('Ordonnances');

        foreach ($meds as $order => $medId) {
            $med = $this->medications[$medId];
            $form = Str::lower($med->dosage_form ?? 'comprimé');
            $this->buffer['prescription_lines'][] = [
                'prescription_id' => $prescriptionId,
                'medication_id' => $medId,
                'medication_name' => $med->name,
                'dosage' => $form === 'inhalateur' ? '2 bouffées' : ($form === 'sachet' ? '1 sachet' : '1 '.$form),
                'frequency' => $c['chronic'] ? $this->pick(['1 fois/jour', '2 fois/jour', 'Matin et soir']) : $this->pick($this->frequencies),
                'duration_days' => $c['chronic'] ? $this->pick([30, 60, 90]) : $this->pick([5, 7, 7, 10, 14]),
                'route' => $form === 'inhalateur' ? 'Inhalée' : 'Orale',
                'instructions' => $this->chance(30) ? $this->pick(['Pendant les repas', 'À jeun le matin', 'Le soir au coucher']) : null,
                'quantity' => $c['chronic'] ? mt_rand(1, 3) : 1,
                'sort_order' => $order,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
    }

    private function seedLabTest(int $consultationId, array $patient, int $doctorId, array $c, Carbon $date): void
    {
        $daysAgo = $date->diffInDays($this->now);
        $status = match (true) {
            $daysAgo > 10 => $this->chance(95) ? 'completed' : 'cancelled',
            $daysAgo > 3 => $this->pick(['completed', 'completed', 'pending']),
            default => $this->pick(['requested', 'pending']),
        };
        $resultDate = $status === 'completed' ? $date->copy()->addDays(mt_rand(1, min(5, max(1, (int) $daysAgo)))) : null;

        $labTestId = DB::table('lab_tests')->insertGetId([
            'request_number' => $this->nextNumber('lab_tests', 'request_number', 'BIO'),
            'patient_id' => $patient['id'],
            'doctor_id' => $doctorId,
            'consultation_id' => $consultationId,
            'requested_at' => $date->toDateString(),
            'result_date' => $resultDate?->toDateString(),
            'status' => $status,
            'laboratory_name' => $this->pick($this->labs),
            'clinical_info' => 'Bilan — '.$c['diag'],
            'doctor_interpretation' => $status === 'completed' && $this->chance(60) ? $this->pick(['Résultats dans les limites normales.', 'Légère perturbation, à contrôler dans 3 mois.', 'Résultats en faveur du diagnostic. Adapter le traitement.']) : null,
            'created_by' => $doctorId,
            'created_at' => $date,
            'updated_at' => $resultDate ?? $date,
        ]);
        $this->stat('Analyses');

        if ($status !== 'completed') {
            return;
        }

        foreach ($c['labs'] as $typeId) {
            if (! isset($this->labTypes[$typeId])) {
                continue;
            }
            $t = $this->labTypes[$typeId];
            $min = (float) ($t->normal_min ?? 0);
            $max = (float) ($t->normal_max ?? 100);
            $range = max($max - $min, 0.1);
            $r = mt_rand(1, 100);

            if ($r <= 75) {
                $value = $min + $range * mt_rand(10, 90) / 100;
                $flag = 'normal';
            } elseif ($r <= 87) {
                $value = $max + $range * mt_rand(5, 40) / 100;
                $flag = 'high';
            } elseif ($r <= 96) {
                $value = max(0, $min - $range * mt_rand(5, 30) / 100);
                $flag = 'low';
            } else {
                $value = $max + $range * mt_rand(60, 120) / 100;
                $flag = 'critical';
            }

            $this->buffer['lab_test_results'][] = [
                'lab_test_id' => $labTestId,
                'lab_test_type_id' => $typeId,
                'parameter_name' => $t->name,
                'value' => (string) round($value, $range < 5 ? 2 : 1),
                'unit' => $t->unit,
                'normal_min' => $t->normal_min,
                'normal_max' => $t->normal_max,
                'flag' => $flag,
                'notes' => null,
                'created_at' => $resultDate,
                'updated_at' => $resultDate,
            ];
        }
    }

    private function seedPayment(int $consultationId, array $patient, int $doctorId, string $type, string $conditionKey, Carbon $date): void
    {
        $mainCode = match ($type) {
            'specialized', 'control' => isset($this->doctors[$doctorId]) && ! str_contains(Str::lower((string) $this->doctors[$doctorId]), 'générale') ? 'CSP' : 'CS',
            'emergency' => 'CSU',
            'teleconsultation' => 'CTL',
            default => 'CS',
        };
        $codes = [$mainCode];
        if ($type !== 'teleconsultation') {
            if (in_array($conditionKey, ['hta', 'palpit'], true) && $this->chance(50)) {
                $codes[] = 'ECG';
            }
            if (in_array($conditionKey, ['asthme', 'bronchite'], true) && $this->chance(60)) {
                $codes[] = 'SPO2';
            }
            if ($this->chance(6)) {
                $codes[] = 'INJIM';
            }
            if (in_array($conditionKey, ['gastro', 'migraine'], true) && $this->chance(15)) {
                $codes[] = 'INJIV';
            }
        }

        $lines = [];
        $subtotal = 0;
        foreach ($codes as $code) {
            $act = $this->acts[$code] ?? null;
            $price = $act ? (float) $act->price : 200.0;
            $lines[] = ['medical_act_id' => $act?->id, 'description' => $act?->name ?? 'Consultation', 'unit_price' => $price];
            $subtotal += $price;
        }

        $discount = $this->chance(8) ? round($subtotal * $this->pick([0.1, 0.2, 0.5]), 2) : 0;
        $total = round($subtotal - $discount, 2);
        $insurance = $patient['insurance'] && $this->chance(40) ? round($total * $this->pick([0.7, 0.8]), 2) : 0;
        $net = round($total - $insurance, 2);

        $daysAgo = $date->diffInDays($this->now);
        $r = mt_rand(1, 100);
        $status = match (true) {
            $daysAgo > 30 => $r <= 92 ? 'paid' : ($r <= 96 ? 'partial' : ($r <= 98 ? 'pending' : 'cancelled')),
            default => $r <= 78 ? 'paid' : ($r <= 88 ? 'partial' : 'pending'),
        };

        [$paid, $due] = match ($status) {
            'paid' => [$net, 0],
            'partial' => [round($net * $this->pick([0.3, 0.5, 0.6]), 2), 0],
            default => [0, $net],
        };
        if ($status === 'partial') {
            $due = round($net - $paid, 2);
        }

        $method = match (true) {
            $status === 'pending' || $status === 'cancelled' => null,
            $insurance > 0 => $this->pick(['insurance', 'mixed']),
            default => $this->pick(['cash', 'cash', 'cash', 'cash', 'bank_transfer']),
        };

        $paymentId = DB::table('payments')->insertGetId([
            'invoice_number' => $this->nextNumber('payments', 'invoice_number', 'FAC'),
            'patient_id' => $patient['id'],
            'doctor_id' => $doctorId,
            'consultation_id' => $consultationId,
            'status' => $status,
            'payment_method' => $method,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'insurance_coverage' => $insurance,
            // Même formule que l'application : total = sous-total − remise − part assurance
            'total_amount' => $net,
            'amount_paid' => $paid,
            'amount_due' => $due,
            'payment_date' => $date->toDateString(), // sert aussi de date de facture dans les rapports
            'due_date' => in_array($status, ['pending', 'partial'], true) ? $date->copy()->addDays(30)->toDateString() : null,
            'notes' => $discount > 0 ? 'Remise accordée' : null,
            'created_by' => $this->secretaryId ?? $doctorId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $this->stat('Paiements');

        foreach ($lines as $line) {
            $this->buffer['payment_lines'][] = [
                'payment_id' => $paymentId,
                'medical_act_id' => $line['medical_act_id'],
                'description' => $line['description'],
                'quantity' => 1,
                'unit_price' => $line['unit_price'],
                'total_price' => $line['unit_price'],
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
    }

    private function seedMedicalReport(int $consultationId, array $patient, int $doctorId, array $c, string $conditionKey, Carbon $date): void
    {
        $type = match (true) {
            $conditionKey === 'bilan' => 'fitness_certificate',
            in_array($conditionKey, ['lombalgie', 'bronchite', 'gastro', 'ivrs'], true) => 'sick_leave',
            $c['chronic'] => $this->pick(['medical_report', 'referral']),
            default => $this->pick(['medical_report', 'sick_leave', 'other']),
        };
        $titles = [
            'fitness_certificate' => 'Certificat d\'aptitude physique',
            'sick_leave' => 'Certificat d\'arrêt de travail',
            'medical_report' => 'Compte-rendu de consultation',
            'referral' => 'Lettre d\'orientation spécialiste',
            'other' => 'Certificat médical',
        ];
        $days = $type === 'sick_leave' ? mt_rand(2, 10) : null;
        $name = DB::table('patients')->where('id', $patient['id'])->selectRaw("CONCAT(first_name, ' ', last_name) as n")->value('n');

        $content = match ($type) {
            'sick_leave' => "Je soussigné(e), certifie avoir examiné ce jour M./Mme {$name}.\nSon état de santé nécessite un arrêt de travail de {$days} jours, sauf complications.",
            'fitness_certificate' => "Je soussigné(e), certifie avoir examiné ce jour M./Mme {$name}.\nL'examen clinique ne révèle aucune contre-indication à la pratique des activités sportives.",
            'referral' => "Cher(e) confrère,\nJe vous adresse M./Mme {$name} pour avis spécialisé.\nDiagnostic : {$c['diag']}.\nMerci de votre prise en charge. Confraternellement.",
            default => "Compte-rendu de consultation de M./Mme {$name}.\nDiagnostic : {$c['diag']}.\nConduite à tenir : traitement prescrit et surveillance.",
        };

        DB::table('medical_reports')->insert([
            'report_number' => $this->nextNumber('medical_reports', 'report_number', 'RPT'),
            'patient_id' => $patient['id'],
            'doctor_id' => $doctorId,
            'consultation_id' => $consultationId,
            'type' => $type,
            'title' => $titles[$type],
            'content' => $content,
            'report_date' => $date->toDateString(),
            'valid_from' => $days ? $date->toDateString() : null,
            'valid_until' => $days ? $date->copy()->addDays($days - 1)->toDateString() : null,
            'sick_leave_days' => $days,
            'is_printed' => $this->chance(90),
            'created_by' => $doctorId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $this->stat('Rapports médicaux');
    }

    private function seedMedicalImage(int $consultationId, array $patient, int $doctorId, string $conditionKey, Carbon $date): void
    {
        [$category, $title, $bodyPart] = match ($conditionKey) {
            'bronchite', 'asthme', 'ivrs' => ['chest_xray', 'Radiographie thoracique de face', 'Thorax'],
            'lombalgie' => ['limb_xray', 'Radiographie du rachis lombaire', 'Colonne lombaire'],
            'arthrose' => ['limb_xray', 'Radiographie du genou', $this->pick(['Genou droit', 'Genou gauche'])],
            'gastrite', 'gastro', 'rgo' => ['ultrasound', 'Échographie abdominale', 'Abdomen'],
            'migraine' => ['ct_scan', 'Scanner cérébral', 'Crâne'],
            default => [$this->pick(['ultrasound', 'chest_xray']), 'Imagerie de contrôle', null],
        };
        $imageDate = $date->copy()->addDays(mt_rand(1, 5));

        DB::table('medical_images')->insert([
            'patient_id' => $patient['id'],
            'doctor_id' => $doctorId,
            'consultation_id' => $consultationId,
            'category' => $category,
            'title' => $title,
            'description' => 'Examen demandé lors de la consultation du '.$date->format('d/m/Y').'.',
            'file_path' => 'medical-images/demo-placeholder.jpg',
            'file_type' => 'jpg',
            'file_size' => mt_rand(300000, 4500000),
            'radiological_report' => $this->chance(70) ? $this->pick(['Pas d\'anomalie décelable.', 'Aspect normal pour l\'âge.', 'Discrètes lésions dégénératives.', 'Opacité basale droite évocatrice d\'un foyer infectieux.']) : null,
            'image_date' => $imageDate->toDateString(),
            'body_part' => $bodyPart,
            'created_by' => $doctorId,
            'created_at' => $imageDate,
            'updated_at' => $imageDate,
        ]);
        $this->stat('Images médicales');
    }

    private function seedDocument(int $consultationId, array $patient, int $doctorId, Carbon $date): void
    {
        $category = $this->pick(['hospital_report', 'external_results', 'insurance', 'insurance', 'prescription_external', 'medical_certificate']);
        $labels = ['medical_certificate' => 'Certificat médical', 'hospital_report' => 'Compte-rendu hospitalier', 'external_results' => 'Résultats externes', 'insurance' => 'Feuille de soins mutuelle', 'prescription_external' => 'Ordonnance externe', 'other' => 'Document'];

        DB::table('documents')->insert([
            'patient_id' => $patient['id'],
            'consultation_id' => $consultationId,
            'category' => $category,
            'title' => $labels[$category].' — '.$date->format('d/m/Y'),
            'description' => $this->chance(50) ? 'Document apporté par le patient.' : null,
            'file_path' => 'documents/demo-placeholder.pdf',
            'file_name' => Str::slug($labels[$category]).'_'.$patient['id'].'_'.$date->format('Ymd').'.pdf',
            'file_type' => 'pdf',
            'file_size' => mt_rand(60000, 1800000),
            'document_date' => $date->toDateString(),
            'source' => $category === 'insurance' ? ($patient['insurance'] ?? 'Mutuelle') : $this->pick(['CHU Ibn Rochd', 'Clinique Atlas', 'Hôpital Cheikh Khalifa', 'Laboratoire externe', 'Patient lui-même']),
            'uploaded_by' => $this->secretaryId ?? $doctorId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $this->stat('Documents');
    }

    // ─────────────────────────────────────────────────────────────────
    private function seedMedicalHistories(array $newPatients): void
    {
        $rows = [];
        $doctorIds = array_keys($this->doctors);

        foreach ($newPatients as $p) {
            $by = $this->pick($doctorIds);
            $at = $p['created_at'];
            $base = ['patient_id' => $p['id'], 'description' => null, 'date_occurred' => null, 'relation' => null, 'habit_type' => null, 'habit_frequency' => null, 'created_by' => $by, 'created_at' => $at, 'updated_at' => $at];

            if ($p['chronic']) {
                $rows[] = array_merge($base, ['type' => 'medical', 'title' => $this->conditions[$p['chronic']]['diag'], 'description' => 'Diagnostiqué il y a '.mt_rand(1, 15).' ans. Sous traitement.', 'date_occurred' => $at->copy()->subYears(mt_rand(1, 15))->toDateString()]);
            }
            if ($this->chance(40)) {
                $rows[] = array_merge($base, ['type' => 'family', 'title' => $this->pick(['HTA', 'Diabète de type 2', 'Cardiopathie ischémique', 'Cancer du sein', 'Asthme']), 'relation' => $this->pick(['Père', 'Mère', 'Frère', 'Sœur', 'Grand-parent'])]);
            }
            if ($this->chance(20)) {
                $rows[] = array_merge($base, ['type' => 'surgical', 'title' => $this->pick(['Appendicectomie', 'Cholécystectomie', 'Cure de hernie inguinale', 'Césarienne', 'Amygdalectomie']), 'description' => 'Suites opératoires simples.', 'date_occurred' => Carbon::create(mt_rand(2000, 2024), mt_rand(1, 12), mt_rand(1, 28))->toDateString()]);
            }
            if ($this->chance(12)) {
                $rows[] = array_merge($base, ['type' => 'allergy', 'title' => $this->pick(['Allergie à la Pénicilline', 'Allergie aux AINS', 'Allergie aux Sulfamides', 'Allergie au pollen']), 'description' => 'Réaction documentée. À éviter.']);
            }
            if ($p['age'] >= 18 && $this->chance(25)) {
                $rows[] = array_merge($base, ['type' => 'habit', 'title' => 'Tabagisme', 'description' => $this->pick(['Fumeur actif', 'Ex-fumeur']), 'habit_type' => 'smoking', 'habit_frequency' => mt_rand(5, 20).' cigarettes/jour']);
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('medical_histories')->insert($chunk);
        }
        $this->stat('Antécédents', count($rows));
    }

    // ── Utilitaires ──────────────────────────────────────────────────
    private function flushBuffers(): void
    {
        foreach ($this->buffer as $table => $rows) {
            foreach (array_chunk($rows, 300) as $chunk) {
                DB::table($table)->insert($chunk);
            }
            $this->buffer[$table] = [];
        }
    }

    /** Prochain numéro PREFIX-2026-NNNNN, à partir du plus grand existant. */
    private function nextNumber(string $table, string $column, string $prefix): string
    {
        $base = $prefix.'-'.self::YEAR.'-';
        if (! isset($this->counters[$base])) {
            $last = DB::table($table)->where($column, 'like', $base.'%')
                ->orderByRaw("LENGTH({$column}) DESC")->orderByDesc($column)->value($column);
            $this->counters[$base] = $last ? (int) substr($last, strlen($base)) : 0;
        }

        return sprintf('%s%05d', $base, ++$this->counters[$base]);
    }

    private function uniqueCin(): string
    {
        do {
            $cin = $this->pick(['BE', 'BK', 'BJ', 'BH', 'AB', 'A', 'J', 'BB']).mt_rand(100000, 999999);
        } while (isset($this->usedCins[$cin]));
        $this->usedCins[$cin] = true;

        return $cin;
    }

    private function pick(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    private function stat(string $key, int $n = 1): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + $n;
    }
}
