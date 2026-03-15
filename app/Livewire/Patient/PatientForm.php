<?php

namespace App\Livewire\Patient;

use App\Models\Patient;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PatientForm extends Component
{
    use WithFileUploads;

    public ?Patient $patient = null;
    public bool $isEdit = false;

    // Champs du formulaire
    public string $first_name = '';
    public string $last_name = '';
    public string $cin = '';
    public string $ip_number = '';
    public string $birth_date = '';
    public string $gender = '';
    public string $phone = '';
    public string $phone_secondary = '';
    public string $email = '';
    public string $address = '';
    public string $city = '';
    public string $blood_group = '';
    public string $known_allergies = '';
    public string $profession = '';
    public string $insurance_name = '';
    public string $insurance_number = '';
    public string $notes = '';
    public $photo = null;

    protected function rules(): array
    {
        $id = $this->patient?->id;

        return [
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'cin'              => "nullable|string|max:20|unique:patients,cin,{$id}",
            'ip_number'        => 'nullable|string|max:30',
            'birth_date'       => 'required|date|before:today',
            'gender'           => 'required|in:male,female',
            'phone'            => 'required|string|max:20',
            'phone_secondary'  => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150',
            'address'          => 'nullable|string|max:500',
            'city'             => 'nullable|string|max:100',
            'blood_group'      => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'known_allergies'  => 'nullable|string|max:1000',
            'profession'       => 'nullable|string|max:100',
            'insurance_name'   => 'nullable|string|max:150',
            'insurance_number' => 'nullable|string|max:50',
            'notes'            => 'nullable|string|max:2000',
            'photo'            => 'nullable|image|max:2048',
        ];
    }

    public function mount(?Patient $patient = null): void
    {
        if ($patient && $patient->exists) {
            $this->patient         = $patient;
            $this->isEdit          = true;
            $this->first_name      = $patient->first_name ?? '';
            $this->last_name       = $patient->last_name ?? '';
            $this->cin             = $patient->cin ?? '';
            $this->ip_number       = $patient->ip_number ?? '';
            $this->birth_date      = $patient->birth_date?->format('Y-m-d') ?? '';
            $this->gender          = $patient->gender ?? '';
            $this->phone           = $patient->phone ?? '';
            $this->phone_secondary = $patient->phone_secondary ?? '';
            $this->email           = $patient->email ?? '';
            $this->address         = $patient->address ?? '';
            $this->city            = $patient->city ?? '';
            $this->blood_group     = $patient->blood_group ?? '';
            $this->known_allergies = $patient->known_allergies ?? '';
            $this->profession      = $patient->profession ?? '';
            $this->insurance_name  = $patient->insurance_name ?? '';
            $this->insurance_number= $patient->insurance_number ?? '';
            $this->notes           = $patient->notes ?? '';
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'first_name'       => $this->first_name,
            'last_name'        => $this->last_name,
            'cin'              => $this->cin ?: null,
            'ip_number'        => $this->ip_number ?: null,
            'birth_date'       => $this->birth_date,
            'gender'           => $this->gender,
            'phone'            => $this->phone,
            'phone_secondary'  => $this->phone_secondary ?: null,
            'email'            => $this->email ?: null,
            'address'          => $this->address ?: null,
            'city'             => $this->city ?: null,
            'blood_group'      => $this->blood_group ?: null,
            'known_allergies'  => $this->known_allergies ?: null,
            'profession'       => $this->profession ?: null,
            'insurance_name'   => $this->insurance_name ?: null,
            'insurance_number' => $this->insurance_number ?: null,
            'notes'            => $this->notes ?: null,
            'created_by'       => auth()->id(),
        ];

        // Gestion de la photo
        if ($this->photo) {
            if ($this->patient?->photo_path) {
                Storage::disk('public')->delete($this->patient->photo_path);
            }
            $data['photo_path'] = $this->photo->store('patients/photos', 'public');
        }

        if ($this->isEdit) {
            $this->authorize('update', $this->patient);
            $this->patient->update($data);
            session()->flash('success', 'Patient mis à jour avec succès.');
        } else {
            $this->authorize('create', Patient::class);
            Patient::create($data);
            session()->flash('success', 'Patient créé avec succès.');
        }

        $this->redirect(route('patients.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.patient.patient-form')
            ->layout('layouts.cabinet', [
                'title' => $this->isEdit ? 'Modifier Patient' : 'Nouveau Patient',
                'header' => $this->isEdit ? 'Modifier le Patient' : 'Nouveau Patient',
                'headerIcon' => 'icon-user-plus',
                'breadcrumbs' => [
                    ['label' => 'Patients', 'url' => route('patients.index')],
                    ['label' => $this->isEdit ? 'Modifier' : 'Nouveau'],
                ],
            ]);
    }
}
