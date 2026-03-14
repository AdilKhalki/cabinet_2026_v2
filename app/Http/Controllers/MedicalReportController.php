<?php

namespace App\Http\Controllers;

use App\Models\MedicalReport;
use Barryvdh\DomPDF\Facade\Pdf;

class MedicalReportController extends Controller
{
    public function pdf(MedicalReport $medicalReport)
    {
        $this->authorize('view', $medicalReport->patient);

        $medicalReport->load(['patient', 'doctor']);

        $pdf = Pdf::loadView('pdf.medical-report', compact('medicalReport'))
            ->setPaper('a4', 'portrait');

        $medicalReport->update(['is_printed' => true]);

        return $pdf->download("rapport-{$medicalReport->report_number}.pdf");
    }
}
