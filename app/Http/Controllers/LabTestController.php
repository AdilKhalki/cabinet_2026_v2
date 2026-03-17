<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class LabTestController extends Controller
{
    public function pdf(LabTest $labTest)
    {
        Gate::authorize('view', $labTest->patient);

        $labTest->load(['patient', 'doctor', 'results.labTestType']);

        $pdf = Pdf::loadView('pdf.lab-test', compact('labTest'))
            ->setPaper('a5', 'portrait');

        return $pdf->download("analyse-{$labTest->request_number}.pdf");
    }
}
