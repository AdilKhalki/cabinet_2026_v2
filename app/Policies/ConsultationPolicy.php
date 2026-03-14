<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Consultation $consultation): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('medecin')) return $consultation->doctor_id === $user->id;
        return false; // La secrétaire ne voit pas les consultations
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medecin']);
    }

    public function update(User $user, Consultation $consultation): bool
    {
        if ($user->hasRole('admin')) return true;
        return $user->hasRole('medecin') && $consultation->doctor_id === $user->id;
    }

    public function delete(User $user, Consultation $consultation): bool
    {
        return $user->hasRole('admin');
    }
}
