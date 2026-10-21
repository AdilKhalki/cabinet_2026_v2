<?php

namespace App\Policies;

use App\Models\LabTest;
use App\Models\User;

class LabTestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view lab-tests');
    }

    public function view(User $user, LabTest $labTest): bool
    {
        return $user->hasPermissionTo('view lab-tests') && $this->ownsOrSupervises($user, $labTest);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create lab-tests');
    }

    public function update(User $user, LabTest $labTest): bool
    {
        return $user->hasPermissionTo('edit lab-tests') && $this->ownsOrSupervises($user, $labTest);
    }

    public function delete(User $user, LabTest $labTest): bool
    {
        return $user->hasPermissionTo('edit lab-tests') && $this->ownsOrSupervises($user, $labTest);
    }

    /** Un médecin n'accède qu'à ses propres demandes d'analyses ; l'admin accède à tout. */
    private function ownsOrSupervises(User $user, LabTest $labTest): bool
    {
        return $user->isAdmin() || ! $user->isMedecin() || (int) $labTest->doctor_id === (int) $user->id;
    }
}
