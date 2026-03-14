<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('medecin')) return $appointment->doctor_id === $user->id;
        return $user->hasRole('secretaire');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medecin', 'secretaire']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('medecin')) return $appointment->doctor_id === $user->id;
        return $user->hasRole('secretaire');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['admin', 'secretaire']);
    }
}
