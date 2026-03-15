<?php

namespace App\Http\Controllers;

use App\Models\MedicalReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MedicalReportController extends Controller
{
    public function pdf(MedicalReport $medicalReport)
    {
        Gate::authorize('view', $medicalReport->patient);

        $medicalReport->load(['patient', 'doctor']);

        $report  = $medicalReport;
        $patient = $medicalReport->patient;

        $settings = [];
        if (Storage::exists('cabinet_settings.json')) {
            $settings = json_decode(Storage::get('cabinet_settings.json'), true) ?? [];
        }

        $pdf = Pdf::loadView('pdf.medical-report', compact('report', 'patient', 'settings'))
            ->setPaper('a4', 'portrait');

        $medicalReport->update(['is_printed' => true]);

        return $pdf->download("rapport-{$medicalReport->report_number}.pdf");
    }
}
