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
        return $user->hasPermissionTo('view lab-tests');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create lab-tests');
    }

    public function update(User $user, LabTest $labTest): bool
    {
        return $user->hasPermissionTo('edit lab-tests');
    }

    public function delete(User $user, LabTest $labTest): bool
    {
        return $user->hasPermissionTo('edit lab-tests');
    }
}
