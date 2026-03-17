<?php

namespace App\Livewire\MedicalReport;

use App\Models\Consultation;
use App\Models\MedicalReport;
use App\Models\Patient;
use App\Models\User;
use Livewire\Component;

class MedicalReportForm extends Component
{
    public ?int $reportId = null;

    // Form fields (snake_case matches wire:model in blade)
    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public int|string $consultation_id = '';
    public string $type = 'medical_report';
    public string $title = '';
    public string $content = '';
    public string $report_date = '';
    public string $valid_from = '';
    public string $valid_until = '';
    public ?int $sick_leave_days = null;

    public function mount($medicalReport = null, ?int $patientId = null): void
    {
        $this->report_date = now()->format('Y-m-d');
        $this->doctor_id   = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($patientId) {
            $this->patient_id = $patientId;
        }

        if ($patientId = request()->query('patient')) {
            $this->patient_id = (int) $patientId;
        }

        if ($medicalReport) {
            $report = $medicalReport instanceof MedicalReport
                ? $medicalReport
                : MedicalReport::findOrFail($medicalReport);
            $this->reportId = $report->id;
            $this->patient_id      = $report->patient_id;
            $this->doctor_id       = $report->doctor_id ?? '';
            $this->consultation_id = $report->consultation_id ?? '';
            $this->type            = $report->type;
            $this->title           = $report->title;
            $this->content         = $report->content ?? '';
            $this->report_date     = $report->report_date?->format('Y-m-d') ?? now()->format('Y-m-d');
            $this->valid_from      = $report->valid_from?->format('Y-m-d') ?? '';
            $this->valid_until     = $report->valid_until?->format('Y-m-d') ?? '';
            $this->sick_leave_days = $report->sick_leave_days;
        }
    }

    public function updatedType(): void
    {
        if ($this->type === 'sick_leave' && !$this->valid_from) {
            $this->valid_from = now()->format('Y-m-d');
        }
    }

    public function applyTemplate(string $type): void
    {
        $templates = [
            'sick_leave' => "Je soussigné(e), Dr. [DOCTEUR], certifie avoir examiné ce jour le/la patient(e) [PATIENT], né(e) le [DATE_NAISSANCE].\n\nAu vu de son état de santé actuel, je lui prescris un arrêt de travail de [JOURS_ARRET] jours, du [DATE_DEBUT] au [DATE_FIN] inclus.\n\nFait à [VILLE], le [DATE_RAPPORT]",

            'fitness_certificate' => "Je soussigné(e), Dr. [DOCTEUR], certifie avoir examiné ce jour le/la patient(e) [PATIENT], né(e) le [DATE_NAISSANCE].\n\nÀ l'issue de cet examen, je le/la déclare :\n\n☑ APTE à [activité / poste / sport]\n\nCette aptitude est valable jusqu'au [DATE_FIN].\n\nFait à [VILLE], le [DATE_RAPPORT]",

            'medical_certificate' => "Je soussigné(e), Dr. [DOCTEUR], certifie avoir examiné ce jour le/la patient(e) [PATIENT], né(e) le [DATE_NAISSANCE].\n\nExamen clinique :\n[RÉSULTATS DE L'EXAMEN]\n\nDiagnostic :\n[DIAGNOSTIC]\n\nConclusion :\n[CONCLUSION]\n\nFait à [VILLE], le [DATE_RAPPORT]",

            'referral' => "Confrère/Consœur,\n\nJ'ai l'honneur de vous adresser mon/ma patient(e), [PATIENT], né(e) le [DATE_NAISSANCE], pour [MOTIF DE LA RÉFÉRENCE].\n\nAntécédents :\n[ANTÉCÉDENTS PERTINENTS]\n\nTraitement en cours :\n[TRAITEMENTS ACTUELS]\n\nJe vous serais reconnaissant(e) de bien vouloir prendre en charge ce/cette patient(e) et de me faire part de vos conclusions.\n\nAvec mes cordiales salutations,\nDr. [DOCTEUR]\nFait à [VILLE], le [DATE_RAPPORT]",

            'surgery_report' => "COMPTE-RENDU OPÉRATOIRE\n\nPatient(e) : [PATIENT]\nDate de naissance : [DATE_NAISSANCE]\nDate de l'intervention : [DATE_RAPPORT]\nChirurgien : Dr. [DOCTEUR]\nAnesthésiste : Dr. _______________\n\nIndication opératoire :\n[INDICATION]\n\nTechnique opératoire :\n[DESCRIPTION DE LA TECHNIQUE]\n\nConstatations peropératoires :\n[CONSTATATIONS]\n\nSuites opératoires immédiates :\n[SUITES]\n\nFait à [VILLE], le [DATE_RAPPORT]",
        ];

        if (isset($templates[$type])) {
            $this->type    = $type;
            $this->content = $this->replacePlaceholders($templates[$type]);
        }
    }

    protected function replacePlaceholders(string $template): string
    {
        // Patient info
        $patient = $this->patient_id ? Patient::find($this->patient_id) : null;
        $patientName = $patient ? $patient->full_name : '[PATIENT]';
        $birthDate = $patient && $patient->birth_date
            ? $patient->birth_date->format('d/m/Y')
            : '[DATE_NAISSANCE]';

        // Doctor info
        $doctor = $this->doctor_id ? User::find($this->doctor_id) : null;
        $doctorName = $doctor ? $doctor->name : '[DOCTEUR]';

        // Cabinet city from settings
        $ville = '[VILLE]';
        if (\Illuminate\Support\Facades\Storage::exists('cabinet_settings.json')) {
            $settings = json_decode(\Illuminate\Support\Facades\Storage::get('cabinet_settings.json'), true) ?? [];
            $ville = $settings['cabinet_city'] ?? '[VILLE]';
        }

        // Dates
        $dateRapport = $this->report_date
            ? \Carbon\Carbon::parse($this->report_date)->format('d/m/Y')
            : now()->format('d/m/Y');

        $dateDebut = $this->valid_from
            ? \Carbon\Carbon::parse($this->valid_from)->format('d/m/Y')
            : '[DATE_DEBUT]';

        $dateFin = $this->valid_until
            ? \Carbon\Carbon::parse($this->valid_until)->format('d/m/Y')
            : '[DATE_FIN]';

        $joursArret = $this->sick_leave_days
            ? (string) $this->sick_leave_days
            : '[JOURS_ARRET]';

        return str_replace(
            ['[PATIENT]', '[DATE_NAISSANCE]', '[DOCTEUR]', '[VILLE]', '[DATE_RAPPORT]', '[DATE_DEBUT]', '[DATE_FIN]', '[JOURS_ARRET]'],
            [$patientName, $birthDate, $doctorName, $ville, $dateRapport, $dateDebut, $dateFin, $joursArret],
            $template
        );
    }

    public function save(): void
    {
        $this->validate([
            'patient_id'      => 'required|exists:patients,id',
            'doctor_id'       => 'required|exists:users,id',
            'type'            => 'required|in:' . implode(',', array_keys(MedicalReport::TYPE_LABELS)),
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'report_date'     => 'required|date',
            'valid_from'      => 'nullable|date',
            'valid_until'     => 'nullable|date|after_or_equal:valid_from',
            'sick_leave_days' => 'nullable|integer|min:1',
        ]);

        $data = [
            'patient_id'      => $this->patient_id,
            'doctor_id'       => $this->doctor_id,
            'consultation_id' => $this->consultation_id ?: null,
            'type'            => $this->type,
            'title'           => $this->title,
            'content'         => $this->content,
            'report_date'     => $this->report_date,
            'valid_from'      => $this->valid_from ?: null,
            'valid_until'     => $this->valid_until ?: null,
            'sick_leave_days' => $this->sick_leave_days,
            'created_by'      => auth()->id(),
        ];

        if ($this->reportId) {
            MedicalReport::findOrFail($this->reportId)->update($data);
            session()->flash('success', 'Rapport mis à jour.');
        } else {
            MedicalReport::create($data);
            session()->flash('success', 'Rapport créé.');
        }

        $this->redirect(route('medical-reports.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.medical-report.medical-report-form', [
            'patients'      => Patient::active()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'dossier_number', 'cin', 'ip_number', 'birth_date']),
            'doctors'       => User::doctors()->active()->orderBy('name')->get(),
            'consultations' => Consultation::with('patient')
                ->when($this->patient_id, fn($q) => $q->where('patient_id', $this->patient_id))
                ->orderByDesc('consultation_date')
                ->take(50)
                ->get(),
            'types'    => MedicalReport::TYPE_LABELS,
            'reportId' => $this->reportId,
        ])->layout('layouts.cabinet', [
            'title'      => $this->reportId ? 'Modifier Rapport' : 'Nouveau Rapport',
            'header'     => $this->reportId ? 'Modifier Rapport Médical' : 'Nouveau Rapport Médical',
            'headerIcon' => 'icon-clipboard',
            'breadcrumbs' => [
                ['label' => 'Rapports Médicaux', 'url' => route('medical-reports.index')],
                ['label' => $this->reportId ? 'Modifier' : 'Nouveau'],
            ],
        ]);
    }
}
