<?php

namespace App\Livewire\Admin;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\LabTest;
use App\Models\MedicalImage;
use App\Models\MedicalReport;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $roleFilter = '';

    public int $perPage = 15;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'medecin';

    public string $phone = '';

    public string $speciality = '';

    public bool $is_active = true;

    /** Relations cliniques supprimées en cascade (FK cascadeOnDelete) si l'utilisateur est supprimé */
    private const CLINICAL_RELATIONS = [
        'appointments' => Appointment::class,
        'consultations' => Consultation::class,
        'prescriptions' => Prescription::class,
        'lab_tests' => LabTest::class,
        'medical_images' => MedicalImage::class,
        'medical_reports' => MedicalReport::class,
        'payments' => Payment::class,
    ];

    /** Vérifiée à chaque requête (rendu initial ET appels d'actions Livewire) */
    public function boot(): void
    {
        abort_unless(auth()->user()?->can('manage users'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::with('roles')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn ($q) => $q->role($this->roleFilter))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'email', 'password', 'password_confirmation', 'phone', 'speciality']);
        $this->role = 'medecin';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetValidation();
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->phone = $user->phone ?? '';
        $this->speciality = $user->speciality ?? '';
        $this->is_active = $user->is_active ?? true;
        $this->role = $user->roles->first()?->name ?? 'medecin';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email'.($this->editingId ? ",{$this->editingId}" : ''),
            'role' => 'required|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'speciality' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', Password::default(), 'confirmed'],
        ];

        $this->validate($rules, [], [
            'name' => 'nom',
            'role' => 'rôle',
            'phone' => 'téléphone',
            'speciality' => 'spécialité',
            'password' => 'mot de passe',
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);

            // Garde-fous : ne pas se retirer soi-même l'accès / ne pas supprimer le dernier admin
            if ($user->is($this->currentUser()) && ! $this->is_active) {
                $this->addError('is_active', 'Vous ne pouvez pas désactiver votre propre compte.');

                return;
            }
            if ($user->hasRole('admin') && ($this->role !== 'admin' || ! $this->is_active) && $this->isLastActiveAdmin($user)) {
                $this->addError('role', 'Impossible : il doit rester au moins un administrateur actif.');

                return;
            }
        }

        $data = [
            'name' => $this->name,
            'email' => strtolower($this->email),
            'phone' => $this->phone ?: null,
            'speciality' => $this->speciality ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            $user->update($data);
            activity()->on($user)->log('Utilisateur modifié');
            session()->flash('success', 'Utilisateur mis à jour.');
        } else {
            $user = User::create($data);
            // Compte créé par l'administrateur : adresse considérée comme vérifiée
            $user->forceFill(['email_verified_at' => now()])->save();
            activity()->on($user)->log('Utilisateur créé');
            session()->flash('success', 'Utilisateur créé avec succès.');
        }

        $user->syncRoles([$this->role]);

        $this->showModal = false;
        $this->reset(['password', 'password_confirmation']);
        unset($this->users);
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->is($this->currentUser())) {
            session()->flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');

            return;
        }

        if ($user->is_active && $user->hasRole('admin') && $this->isLastActiveAdmin($user)) {
            session()->flash('error', 'Impossible : il doit rester au moins un administrateur actif.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        activity()->on($user)->log($user->is_active ? 'Utilisateur activé' : 'Utilisateur désactivé');
        session()->flash('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
        unset($this->users);
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->is($this->currentUser())) {
            session()->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return;
        }

        if ($user->hasRole('admin') && $this->isLastActiveAdmin($user)) {
            session()->flash('error', 'Impossible : il doit rester au moins un administrateur actif.');

            return;
        }

        // Les dossiers médicaux liés seraient supprimés définitivement (cascade) :
        // on impose la désactivation du compte à la place.
        if ($this->hasClinicalRecords($user)) {
            session()->flash('error', 'Cet utilisateur possède des données médicales ou financières (RDV, consultations, paiements…). Désactivez son compte au lieu de le supprimer.');

            return;
        }

        activity()->on($user)->log('Utilisateur supprimé');
        $user->delete();
        session()->flash('success', 'Utilisateur supprimé.');
        unset($this->users);
    }

    private function currentUser(): User
    {
        return auth()->user();
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return ! User::role('admin')
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }

    private function hasClinicalRecords(User $user): bool
    {
        foreach (self::CLINICAL_RELATIONS as $model) {
            $query = $model::query()->where('doctor_id', $user->getKey());
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->withTrashed();
            }
            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        return view('livewire.admin.user-index', [
            'users' => $this->users,
            'roles' => $this->roles,
        ])->layout('layouts.cabinet', [
            'title' => 'Utilisateurs',
            'header' => 'Gestion des Utilisateurs',
            'headerIcon' => 'icon-users',
            'breadcrumbs' => [['label' => 'Utilisateurs']],
        ]);
    }
}
