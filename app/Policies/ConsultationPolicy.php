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
        return $user->hasPermissionTo('view consultations');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create consultations');
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $user->hasPermissionTo('edit consultations');
    }

    public function delete(User $user, Consultation $consultation): bool
    {
        return $user->hasPermissionTo('delete consultations');
    }
}
