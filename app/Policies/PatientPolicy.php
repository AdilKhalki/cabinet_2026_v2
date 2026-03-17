<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view patients');
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('view patients');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create patients');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('edit patients');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('delete patients');
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('delete patients');
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('delete patients');
    }
}
