<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function pdf(Payment $payment)
    {
        Gate::authorize('view', $payment);

        $payment->load(['patient', 'doctor', 'lines.medicalAct']);

        $pdf = Pdf::loadView('pdf.invoice', compact('payment'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("facture-{$payment->invoice_number}.pdf");
    }
}
