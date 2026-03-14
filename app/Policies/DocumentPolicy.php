<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'medecin', 'secretaire']);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['admin', 'medecin']);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['admin', 'medecin']);
    }
}
