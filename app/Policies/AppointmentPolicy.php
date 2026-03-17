<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view appointments');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasPermissionTo('view appointments');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create appointments');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasPermissionTo('edit appointments');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasPermissionTo('delete appointments');
    }
}
