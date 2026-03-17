<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view prescriptions');
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('view prescriptions');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create prescriptions');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('create prescriptions');
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('create prescriptions');
    }

    public function print(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('print prescriptions');
    }
}
