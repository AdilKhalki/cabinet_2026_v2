<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class PrescriptionController extends Controller
{
    public function pdf(Prescription $prescription)
    {
        Gate::authorize('view', $prescription->patient);

        $prescription->load(['patient', 'doctor', 'lines.medication']);

        $pdf = Pdf::loadView('pdf.prescription', compact('prescription'))
            ->setPaper('a5', 'portrait');

        // Marquer comme imprimée
        $prescription->update(['is_printed' => true]);

        return $pdf->download("ordonnance-{$prescription->prescription_number}.pdf");
    }
}
