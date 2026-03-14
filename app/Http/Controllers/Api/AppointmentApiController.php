<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentApiController extends Controller
{
    public function calendar(Request $request): JsonResponse
    {
        $start = $request->query('start', now()->startOfMonth()->toDateString());
        $end   = $request->query('end', now()->endOfMonth()->toDateString());

        $events = Appointment::with(['patient', 'doctor'])
            ->forCalendar($start, $end)
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->get()
            ->map(fn ($appt) => [
                'id'              => $appt->id,
                'title'           => $appt->patient->full_name,
                'start'           => $appt->scheduled_at->toIso8601String(),
                'end'             => $appt->scheduled_end?->toIso8601String(),
                'backgroundColor' => $appt->color ?? '#01a9ac',
                'borderColor'     => $appt->color ?? '#01a9ac',
                'url'             => route('patients.show', $appt->patient_id),
                'extendedProps'   => [
                    'status'       => $appt->status,
                    'status_label' => $appt->status_label,
                    'doctor'       => $appt->doctor->name,
                    'type'         => Appointment::TYPE_LABELS[$appt->consultation_type] ?? '',
                    'dossier'      => $appt->patient->dossier_number,
                    'phone'        => $appt->patient->phone,
                ],
            ]);

        return response()->json($events);
    }
}
