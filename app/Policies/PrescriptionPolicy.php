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
        return $user->hasPermissionTo('view prescriptions') && $this->ownsOrSupervises($user, $prescription);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create prescriptions');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('create prescriptions') && $this->ownsOrSupervises($user, $prescription);
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('create prescriptions') && $this->ownsOrSupervises($user, $prescription);
    }

    public function print(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('print prescriptions') && $this->ownsOrSupervises($user, $prescription);
    }

    /** Un médecin n'accède qu'à ses propres ordonnances ; l'admin accède à tout. */
    private function ownsOrSupervises(User $user, Prescription $prescription): bool
    {
        return $user->isAdmin() || ! $user->isMedecin() || (int) $prescription->doctor_id === (int) $user->id;
    }
}
