<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view consultations');
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return $user->hasPermissionTo('view consultations') && $this->ownsIfDoctor($user, $consultation);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create consultations');
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $user->hasPermissionTo('edit consultations') && $this->ownsIfDoctor($user, $consultation);
    }

    public function delete(User $user, Consultation $consultation): bool
    {
        return $user->hasPermissionTo('delete consultations') && $this->ownsIfDoctor($user, $consultation);
    }

    /**
     * Secret médical : un médecin (non admin) n'accède qu'à ses propres consultations.
     */
    private function ownsIfDoctor(User $user, Consultation $consultation): bool
    {
        if ($user->isAdmin() || ! $user->isMedecin()) {
            return true;
        }

        return (int) $consultation->doctor_id === (int) $user->id;
    }
}
