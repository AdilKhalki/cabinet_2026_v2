<?php

namespace App\Policies;

use App\Models\MedicalImage;
use App\Models\User;

class MedicalImagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view medical-images');
    }

    public function view(User $user, MedicalImage $medicalImage): bool
    {
        return $user->hasPermissionTo('view medical-images');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create medical-images');
    }

    public function update(User $user, MedicalImage $medicalImage): bool
    {
        return $user->hasPermissionTo('edit medical-images');
    }

    public function delete(User $user, MedicalImage $medicalImage): bool
    {
        return $user->hasPermissionTo('delete medical-images');
    }
}
