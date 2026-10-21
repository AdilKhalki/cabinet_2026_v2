<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class AppointmentApiController extends Controller
{
    /** Couleur par statut (prioritaire sur la couleur libre du RDV pour les statuts terminaux). */
    private const STATUS_EVENT_COLORS = [
        'cancelled' => '#9e9e9e',
        'no_show'   => '#616161',
    ];

    /** Fenêtre maximale interrogeable (évite de renvoyer des milliers de RDV d'un coup). */
    private const MAX_RANGE_DAYS = 62;

    public function calendar(Request $request): JsonResponse
    {
        // La route /api n'a pas de middleware de permission : on vérifie ici
        $this->authorize('viewAny', Appointment::class);

        $validated = $request->validate([
            'start'     => ['nullable', 'date'],
            'end'       => ['nullable', 'date', 'after:start'],
            'doctor_id' => ['nullable', 'integer'],
        ]);

        // FullCalendar envoie des dates ISO 8601 avec le décalage du navigateur
        // (ex. 2026-10-19T00:00:00+01:00). Les DATETIME stockés sont des heures « murales »
        // du cabinet : on garde l'heure murale et on ignore le décalage (shiftTimezone).
        $tz = config('app.timezone');
        $start = isset($validated['start'])
            ? Date::parse($validated['start'])->shiftTimezone($tz)
            : now()->startOfMonth();
        $end = isset($validated['end'])
            ? Date::parse($validated['end'])->shiftTimezone($tz)
            : now()->endOfMonth();

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            $end = $start->addDays(self::MAX_RANGE_DAYS);
        }

        $user = $request->user();

        $events = Appointment::with(['patient:id,first_name,last_name,dossier_number,phone', 'doctor:id,name'])
            ->forCalendar($start, $end)
            ->when($user->isMedecin() && ! $user->isAdmin(), fn ($q) => $q->forDoctor($user->id))
            ->when($validated['doctor_id'] ?? null, fn ($q, $doctorId) => $q->where('doctor_id', $doctorId))
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $appt) => [
                'id'              => $appt->id,
                'title'           => $appt->patient?->full_name ?? 'Patient',
                // Sans décalage horaire : FullCalendar les affiche tels quels (heure du cabinet)
                'start'           => $appt->scheduled_at->format('Y-m-d\TH:i:s'),
                'end'             => $appt->scheduled_end?->format('Y-m-d\TH:i:s'),
                'backgroundColor' => self::STATUS_EVENT_COLORS[$appt->status] ?? ($appt->color ?: '#01a9ac'),
                'borderColor'     => self::STATUS_EVENT_COLORS[$appt->status] ?? ($appt->color ?: '#01a9ac'),
                'url'             => route('patients.show', $appt->patient_id),
                'extendedProps'   => [
                    'status'       => $appt->status,
                    'status_label' => $appt->status_label,
                    'doctor'       => $appt->doctor?->name,
                    'type'         => Appointment::TYPE_LABELS[$appt->consultation_type] ?? '',
                    'dossier'      => $appt->patient?->dossier_number,
                    'phone'        => $appt->patient?->phone,
                ],
            ]);

        return response()->json($events);
    }
}
