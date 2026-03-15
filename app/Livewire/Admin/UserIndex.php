<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    // Modal
    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $confirmingDeleteId = null;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'medecin';
    public string $phone = '';
    public string $speciality = '';
    public bool $is_active = true;

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
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn($q) => $q->role($this->roleFilter))
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
        $this->reset(['editingId', 'name', 'email', 'password', 'phone', 'speciality']);
        $this->role = 'medecin';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->phone = $user->phone ?? '';
        $this->speciality = $user->speciality ?? '';
        $this->is_active = $user->is_active ?? true;
        $this->role = $user->roles->first()?->name ?? 'medecin';
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($this->editingId ? ",{$this->editingId}" : ''),
            'role' => 'required|in:admin,medecin,secretaire',
            'phone' => 'nullable|string|max:20',
            'speciality' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ];

        if (!$this->editingId) {
            $rules['password'] = 'required|min:8';
        } else {
            $rules['password'] = 'nullable|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'speciality' => $this->speciality ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($data);
            activity()->on($user)->log('Utilisateur modifié');
            session()->flash('success', 'Utilisateur mis à jour.');
        } else {
            $data['password'] = Hash::make($this->password);
            $user = User::create($data);
            activity()->on($user)->log('Utilisateur créé');
            session()->flash('success', 'Utilisateur créé avec succès.');
        }

        $user->syncRoles([$this->role]);

        $this->showModal = false;
        unset($this->users);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            $user = User::findOrFail($this->confirmingDeleteId);
            activity()->on($user)->log('Utilisateur supprimé');
            $user->delete();
            session()->flash('success', 'Utilisateur supprimé.');
            $this->confirmingDeleteId = null;
            unset($this->users);
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.user-index', [
            'users' => $this->users,
            'roles' => $this->roles,
        ])->layout('layouts.cabinet', [
            'title'      => 'Utilisateurs',
            'header'     => 'Gestion des Utilisateurs',
            'headerIcon' => 'icon-users',
            'breadcrumbs' => [['label' => 'Utilisateurs']],
        ]);
    }
}
