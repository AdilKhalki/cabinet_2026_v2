<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    public function pdf(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['patient', 'doctor', 'lines.medicalAct']);

        $pdf = Pdf::loadView('pdf.invoice', compact('payment'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("facture-{$payment->invoice_number}.pdf");
    }
}
