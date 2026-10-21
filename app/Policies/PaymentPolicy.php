<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view payments');
    }

    public function view(User $user, Payment $payment): bool
    {
        if (! $user->hasPermissionTo('view payments')) {
            return false;
        }

        // Un médecin ne consulte que ses propres factures
        return ! Payment::restrictedToOwn($user) || (int) $payment->doctor_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create payments');
    }

    public function update(User $user, Payment $payment): bool
    {
        // Une facture annulée / remboursée n'est plus modifiable
        return $user->hasPermissionTo('edit payments') && ! $payment->isClosed();
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('edit payments') && ! $payment->isClosed();
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('delete payments');
    }
}
