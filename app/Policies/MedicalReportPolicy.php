<?php

namespace App\Policies;

use App\Models\MedicalReport;
use App\Models\User;

class MedicalReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view medical-reports');
    }

    public function view(User $user, MedicalReport $medicalReport): bool
    {
        return $user->hasPermissionTo('view medical-reports') && $this->ownsOrSupervises($user, $medicalReport);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create medical-reports');
    }

    public function update(User $user, MedicalReport $medicalReport): bool
    {
        return $user->hasPermissionTo('edit medical-reports') && $this->ownsOrSupervises($user, $medicalReport);
    }

    public function delete(User $user, MedicalReport $medicalReport): bool
    {
        return $user->hasPermissionTo('delete medical-reports') && $this->ownsOrSupervises($user, $medicalReport);
    }

    public function print(User $user, MedicalReport $medicalReport): bool
    {
        return $user->hasPermissionTo('print medical-reports') && $this->ownsOrSupervises($user, $medicalReport);
    }

    /** Un médecin n'accède qu'à ses propres rapports ; l'admin accède à tout. */
    private function ownsOrSupervises(User $user, MedicalReport $medicalReport): bool
    {
        return $user->isAdmin() || ! $user->isMedecin() || (int) $medicalReport->doctor_id === (int) $user->id;
    }
}
