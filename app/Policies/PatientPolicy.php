<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Tous les utilisateurs authentifiés
    }

    public function view(User $user, Patient $patient): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medecin', 'secretaire']);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['admin', 'medecin', 'secretaire']);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin');
    }
}
