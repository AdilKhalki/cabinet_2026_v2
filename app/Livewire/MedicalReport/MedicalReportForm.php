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

    public function mount(?int $reportId = null, ?int $patientId = null): void
    {
        $this->report_date = now()->format('Y-m-d');
        $this->doctor_id   = auth()->user()->isMedecin() ? auth()->id() : '';

        if ($patientId) {
            $this->patient_id = $patientId;
        }

        if ($reportId) {
            $this->reportId = $reportId;
            $report = MedicalReport::findOrFail($reportId);
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
            'sick_leave' => "Je soussigné(e), Dr. [NOM], certifie avoir examiné ce jour le/la patient(e) [PRÉNOM NOM].\n\nAu vu de son état de santé actuel, je lui prescris un arrêt de travail de [X] jours, du [DATE DÉBUT] au [DATE FIN] inclus.\n\nFait à [VILLE], le " . now()->format('d/m/Y'),

            'fitness_certificate' => "Je soussigné(e), Dr. [NOM], certifie avoir examiné ce jour le/la patient(e) [PRÉNOM NOM], né(e) le [DATE NAISSANCE].\n\nÀ l'issue de cet examen, je le/la déclare :\n\n☑ APTE à [activité / poste / sport]\n\nCette aptitude est valable jusqu'au [DATE].\n\nFait à [VILLE], le " . now()->format('d/m/Y'),

            'medical_certificate' => "Je soussigné(e), Dr. [NOM], certifie avoir examiné ce jour le/la patient(e) [PRÉNOM NOM], né(e) le [DATE NAISSANCE].\n\nExamen clinique :\n[RÉSULTATS DE L'EXAMEN]\n\nDiagnostic :\n[DIAGNOSTIC]\n\nConclusion :\n[CONCLUSION]\n\nFait à [VILLE], le " . now()->format('d/m/Y'),

            'referral' => "Confrère/Consœur,\n\nJ'ai l'honneur de vous adresser mon/ma patient(e), [PRÉNOM NOM], né(e) le [DATE NAISSANCE], pour [MOTIF DE LA RÉFÉRENCE].\n\nAntécédents :\n[ANTÉCÉDENTS PERTINENTS]\n\nTraitement en cours :\n[TRAITEMENTS ACTUELS]\n\nJe vous serais reconnaissant(e) de bien vouloir prendre en charge ce/cette patient(e) et de me faire part de vos conclusions.\n\nAvec mes cordiales salutations,\nDr. [NOM]\nFait à [VILLE], le " . now()->format('d/m/Y'),

            'surgery_report' => "COMPTE-RENDU OPÉRATOIRE\n\nPatient(e) : [PRÉNOM NOM]\nDate de l'intervention : [DATE]\nChirurgien : Dr. [NOM]\nAnesthésiste : Dr. [NOM]\n\nIndication opératoire :\n[INDICATION]\n\nTechnique opératoire :\n[DESCRIPTION DE LA TECHNIQUE]\n\nConstatations peropératoires :\n[CONSTATATIONS]\n\nSuites opératoires immédiates :\n[SUITES]\n\nFait à [VILLE], le " . now()->format('d/m/Y'),
        ];

        if (isset($templates[$type])) {
            $this->content = $templates[$type];
            $this->type    = $type;
        }
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
            'patients'      => Patient::active()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'dossier_number']),
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
