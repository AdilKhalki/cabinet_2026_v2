<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleIndex extends Component
{
    public bool $showForm = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public array $selectedPermissions = [];

    // Groupes de permissions pour l'affichage
    public const PERMISSION_GROUPS = [
        'Patients' => ['view patients', 'create patients', 'edit patients', 'delete patients'],
        'Rendez-vous' => ['view appointments', 'create appointments', 'edit appointments', 'delete appointments'],
        'Consultations' => ['view consultations', 'create consultations', 'edit consultations', 'delete consultations'],
        'Ordonnances' => ['view prescriptions', 'create prescriptions', 'print prescriptions'],
        'Analyses' => ['view lab-tests', 'create lab-tests', 'edit lab-tests'],
        'Imagerie' => ['view medical-images', 'create medical-images', 'edit medical-images', 'delete medical-images'],
        'Rapports Méd.' => ['view medical-reports', 'create medical-reports', 'edit medical-reports', 'delete medical-reports', 'print medical-reports'],
        'Paiements' => ['view payments', 'create payments', 'edit payments', 'delete payments'],
        'Documents' => ['view documents', 'upload documents', 'delete documents'],
        'Rapports Fin.' => ['view reports', 'generate reports'],
        'Administration' => ['manage users', 'manage roles', 'manage medications', 'view activity-log'],
    ];

    /** Rôles utilisés en dur dans le code (hasRole, scopes…) : ni renommables ni supprimables */
    public const SYSTEM_ROLES = ['admin', 'medecin', 'secretaire'];

    /** Vérifiée à chaque requête (rendu initial ET appels d'actions Livewire) */
    public function boot(): void
    {
        $user = auth()->user();
        abort_unless($user?->can('manage users') && $user->can('manage roles'), 403);
    }

    #[Computed]
    public function roles()
    {
        return Role::withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function allPermissions()
    {
        return Permission::orderBy('name')->get();
    }

    public function openForm(?int $id = null): void
    {
        $this->resetForm();
        if ($id) {
            $role = Role::with('permissions')->findOrFail($id);
            $this->editingId = $id;
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        }
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:50|regex:/^[\pL\pN _-]+$/u|unique:roles,name'.($this->editingId ? ",{$this->editingId}" : ''),
            'selectedPermissions' => 'array',
            'selectedPermissions.*' => 'string|exists:permissions,name',
        ], [], ['name' => 'nom du rôle']);

        if ($this->editingId) {
            $role = Role::findOrFail($this->editingId);

            // Protéger les rôles système (utilisés en dur dans le code)
            if (in_array($role->name, self::SYSTEM_ROLES) && $this->name !== $role->name) {
                $this->addError('name', "Le rôle système « {$role->name} » ne peut pas être renommé.");

                return;
            }

            $role->update(['name' => $this->name]);

            // Le rôle admin conserve toujours toutes les permissions
            // (évite qu'un administrateur se verrouille hors de l'administration).
            $role->syncPermissions(
                $role->name === 'admin' ? Permission::all() : $this->selectedPermissions
            );
            activity()->on($role)->withProperties(['permissions' => $role->permissions()->pluck('name')])->log('Rôle modifié');
            session()->flash('success', "Rôle « {$role->name} » mis à jour.");
        } else {
            $role = Role::create(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            activity()->on($role)->withProperties(['permissions' => $this->selectedPermissions])->log('Rôle créé');
            session()->flash('success', "Rôle « {$role->name} » créé.");
        }

        unset($this->roles);
        $this->closeForm();
    }

    public function toggleGroupPermissions(string $group): void
    {
        $groupPerms = self::PERMISSION_GROUPS[$group] ?? [];
        $existing = Permission::whereIn('name', $groupPerms)->pluck('name')->toArray();

        $allSelected = count(array_intersect($existing, $this->selectedPermissions)) === count($existing);

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $existing));
        } else {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $existing)));
        }
    }

    public function confirmDelete(int $id): void
    {
        $role = Role::findOrFail($id);
        if ($error = $this->deletionError($role)) {
            session()->flash('error', $error);

            return;
        }
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $role = Role::findOrFail($this->deletingId);
        // Re-vérification côté serveur : delete() peut être appelé directement
        if ($error = $this->deletionError($role)) {
            session()->flash('error', $error);
            $this->showDeleteModal = false;
            $this->deletingId = null;

            return;
        }
        $roleName = $role->name;
        activity()->on($role)->log("Rôle « {$roleName} » supprimé");
        $role->delete();
        $this->showDeleteModal = false;
        $this->deletingId = null;
        unset($this->roles);
        session()->flash('success', "Rôle « {$roleName} » supprimé.");
    }

    private function deletionError(Role $role): ?string
    {
        if (in_array($role->name, self::SYSTEM_ROLES)) {
            return "Le rôle « {$role->name} » est un rôle système et ne peut pas être supprimé.";
        }
        if ($role->users()->exists()) {
            return "Le rôle « {$role->name} » est attribué à des utilisateurs : réaffectez-les avant de le supprimer.";
        }

        return null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->selectedPermissions = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.role-index', [
            'roles' => $this->roles,
            'allPermissions' => $this->allPermissions,
            'groups' => self::PERMISSION_GROUPS,
        ])->layout('layouts.cabinet', [
            'title' => 'Rôles & Permissions',
            'header' => 'Rôles & Permissions',
            'headerIcon' => 'icon-shield',
            'breadcrumbs' => [
                ['label' => 'Administration'],
                ['label' => 'Rôles & Permissions'],
            ],
        ]);
    }
}
