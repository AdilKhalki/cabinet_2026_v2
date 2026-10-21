<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CabinetSettings extends Component
{
    use WithFileUploads;

    public string $cabinet_name = '';
    public string $cabinet_address = '';
    public string $cabinet_phone = '';
    public string $cabinet_email = '';
    public string $cabinet_city = '';
    public string $doctor_name = '';
    public string $doctor_speciality = '';
    public string $doctor_license = '';
    public string $pdf_footer_text = '';
    public int $appointment_reminder_hours = 24;

    /** Vérifiée à chaque requête (rendu initial ET appels d'actions Livewire) */
    public function boot(): void
    {
        abort_unless(auth()->user()?->can('manage users'), 403);
    }

    public function mount(): void
    {
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        if (Storage::exists('cabinet_settings.json')) {
            $data = json_decode(Storage::get('cabinet_settings.json'), true) ?? [];
            $this->cabinet_name = $data['cabinet_name'] ?? '';
            $this->cabinet_address = $data['cabinet_address'] ?? '';
            $this->cabinet_phone = $data['cabinet_phone'] ?? '';
            $this->cabinet_email = $data['cabinet_email'] ?? '';
            $this->cabinet_city = $data['cabinet_city'] ?? '';
            $this->doctor_name = $data['doctor_name'] ?? '';
            $this->doctor_speciality = $data['doctor_speciality'] ?? '';
            $this->doctor_license = $data['doctor_license'] ?? '';
            $this->pdf_footer_text = $data['pdf_footer_text'] ?? '';
            $this->appointment_reminder_hours = (int) ($data['appointment_reminder_hours'] ?? 24);
        }
    }

    public function save(): void
    {
        $this->validate([
            'cabinet_name' => 'required|string|max:255',
            'cabinet_address' => 'nullable|string|max:500',
            'cabinet_city' => 'nullable|string|max:100',
            'cabinet_phone' => 'required|string|max:20',
            'cabinet_email' => 'nullable|email|max:255',
            'doctor_name' => 'required|string|max:255',
            'doctor_speciality' => 'nullable|string|max:150',
            'doctor_license' => 'nullable|string|max:50',
            'pdf_footer_text' => 'nullable|string|max:1000',
            'appointment_reminder_hours' => 'required|integer|min:1|max:168',
        ], [], [
            'cabinet_name' => 'nom du cabinet',
            'cabinet_phone' => 'téléphone',
            'doctor_name' => 'nom du médecin',
            'appointment_reminder_hours' => 'délai de rappel',
        ]);

        $data = [
            'cabinet_name' => $this->cabinet_name,
            'cabinet_address' => $this->cabinet_address,
            'cabinet_phone' => $this->cabinet_phone,
            'cabinet_email' => $this->cabinet_email,
            'cabinet_city' => $this->cabinet_city,
            'doctor_name' => $this->doctor_name,
            'doctor_speciality' => $this->doctor_speciality,
            'doctor_license' => $this->doctor_license,
            'pdf_footer_text' => $this->pdf_footer_text,
            'appointment_reminder_hours' => $this->appointment_reminder_hours,
        ];

        Storage::put('cabinet_settings.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        activity()->log('Paramètres cabinet mis à jour');
        session()->flash('success', 'Paramètres enregistrés avec succès.');
    }

    public function render()
    {
        return view('livewire.admin.cabinet-settings')
            ->layout('layouts.cabinet', [
                'title' => 'Paramètres Cabinet',
                'header' => 'Paramètres du Cabinet',
                'headerIcon' => 'icon-settings',
                'breadcrumbs' => [['label' => 'Paramètres Cabinet']],
            ]);
    }
}
