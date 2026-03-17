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
        return $user->hasPermissionTo('view medical-reports');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create medical-reports');
    }

    public function update(User $user, MedicalReport $medicalReport): bool
    {
        return $user->hasPermissionTo('edit medical-reports');
    }

    public function delete(User $user, MedicalReport $medicalReport): bool
    {
        return $user->hasPermissionTo('delete medical-reports');
    }

    public function print(User $user, MedicalReport $medicalReport): bool
    {
        return $user->hasPermissionTo('print medical-reports');
    }
}
