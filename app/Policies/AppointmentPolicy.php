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
        return $user->hasPermissionTo('view appointments') && $this->ownsIfDoctor($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create appointments');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasPermissionTo('edit appointments') && $this->ownsIfDoctor($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasPermissionTo('delete appointments') && $this->ownsIfDoctor($user, $appointment);
    }

    /**
     * Un médecin (non admin) n'agit que sur ses propres rendez-vous.
     */
    private function ownsIfDoctor(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin() || ! $user->isMedecin()) {
            return true;
        }

        return (int) $appointment->doctor_id === (int) $user->id;
    }
}
