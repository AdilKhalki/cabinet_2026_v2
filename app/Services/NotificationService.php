<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use App\Notifications\CabinetNotification;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Notifier les admins + un médecin spécifique (si différent de l'auteur).
     */
    protected static function notifyAdminsAndDoctor(?int $doctorId, string $type, string $title, string $message, ?string $url = null): void
    {
        $notification = new CabinetNotification($type, $title, $message, $url);
        $currentUserId = auth()->id();

        // Notifier tous les admins actifs (sauf l'auteur)
        User::role('admin')->active()->where('id', '!=', $currentUserId)->get()
            ->each->notify($notification);

        // Notifier le médecin concerné (s'il n'est pas l'auteur et pas admin)
        if ($doctorId && $doctorId !== $currentUserId) {
            $doctor = User::active()->find($doctorId);
            if ($doctor && ! $doctor->hasRole('admin')) {
                $doctor->notify($notification);
            }
        }
    }

    /**
     * Notifier uniquement le médecin concerné (s'il est actif et n'est pas l'auteur).
     */
    protected static function notifyDoctor(?int $doctorId, string $type, string $title, string $message, ?string $url = null): void
    {
        if (! $doctorId || $doctorId === auth()->id()) {
            return;
        }

        User::active()->find($doctorId)?->notify(new CabinetNotification($type, $title, $message, $url));
    }

    /**
     * Notifier les secrétaires actives.
     */
    protected static function notifySecretaries(string $type, string $title, string $message, ?string $url = null): void
    {
        $notification = new CabinetNotification($type, $title, $message, $url);
        $currentUserId = auth()->id();

        User::role('secretaire')->active()->where('id', '!=', $currentUserId)->get()
            ->each->notify($notification);
    }

    // ── Rendez-vous ────────────────────────────────────────

    public static function appointmentCreated(int $patientId, ?int $doctorId, string $scheduledAt): void
    {
        $patient = Patient::find($patientId);
        $patientName = $patient ? $patient->full_name : 'Patient';
        $when = Carbon::parse($scheduledAt);
        $date = $when->format('d/m/Y à H:i');
        $message = "RDV créé pour {$patientName} le {$date}";

        // Le médecin est toujours prévenu ; les secrétaires seulement pour un RDV du jour
        // (sinon des milliers de notifications par an pour l'équipe).
        self::notifyDoctor($doctorId, 'appointment', 'Nouveau rendez-vous', $message, route('appointments.index'));

        if ($when->isToday()) {
            self::notifySecretaries('appointment', 'Nouveau rendez-vous aujourd\'hui', $message, route('appointments.index'));
        }
    }

    public static function appointmentCancelled(int $patientId, ?int $doctorId, string $scheduledAt): void
    {
        $patient = Patient::find($patientId);
        $patientName = $patient ? $patient->full_name : 'Patient';
        $date = Carbon::parse($scheduledAt)->format('d/m/Y à H:i');

        self::notifyDoctor(
            $doctorId,
            'alert',
            'Rendez-vous annulé',
            "Le RDV de {$patientName} prévu le {$date} a été annulé",
            route('appointments.index')
        );

        self::notifySecretaries(
            'alert',
            'Rendez-vous annulé',
            "Le RDV de {$patientName} prévu le {$date} a été annulé",
            route('appointments.index')
        );
    }

    // ── Consultation ───────────────────────────────────────

    public static function consultationCreated(int $consultationId, int $patientId, ?int $doctorId): void
    {
        $patient = Patient::find($patientId);
        $patientName = $patient ? $patient->full_name : 'Patient';

        self::notifyAdminsAndDoctor(
            $doctorId,
            'appointment',
            'Nouvelle consultation',
            "Consultation créée pour {$patientName}",
            route('consultations.show', $consultationId)
        );
    }

    // ── Ordonnance ─────────────────────────────────────────

    public static function prescriptionCreated(int $prescriptionId, int $patientId, ?int $doctorId): void
    {
        $patient = Patient::find($patientId);
        $patientName = $patient ? $patient->full_name : 'Patient';

        self::notifyAdminsAndDoctor(
            $doctorId,
            'prescription',
            'Nouvelle ordonnance',
            "Ordonnance créée pour {$patientName}",
            route('prescriptions.show', $prescriptionId)
        );
    }

    // ── Analyse ────────────────────────────────────────────

    public static function labTestCreated(int $labTestId, int $patientId, ?int $doctorId): void
    {
        $patient = Patient::find($patientId);
        $patientName = $patient ? $patient->full_name : 'Patient';

        self::notifyAdminsAndDoctor(
            $doctorId,
            'lab_result',
            'Nouvelle analyse',
            "Demande d'analyse créée pour {$patientName}",
            route('lab-tests.show', $labTestId)
        );
    }

    // ── Paiement ───────────────────────────────────────────

    public static function paymentCreated(int $paymentId, int $patientId, float $amount, string $status): void
    {
        $patient = Patient::find($patientId);
        $patientName = $patient ? $patient->full_name : 'Patient';
        $statusLabels = ['paid' => 'payé', 'partial' => 'partiel', 'pending' => 'impayé'];
        $statusLabel = $statusLabels[$status] ?? $status;

        self::notifyAdminsAndDoctor(
            null,
            'payment',
            'Nouveau paiement',
            'Paiement de '.number_format($amount, 2)." MAD ({$statusLabel}) pour {$patientName}",
            route('payments.index')
        );
    }

    // ── Rapport médical ────────────────────────────────────

    public static function medicalReportCreated(int $reportId, int $patientId, ?int $doctorId, string $title): void
    {
        $patient = Patient::find($patientId);
        $patientName = $patient ? $patient->full_name : 'Patient';

        self::notifyAdminsAndDoctor(
            $doctorId,
            'system',
            'Nouveau rapport médical',
            "Rapport \"{$title}\" créé pour {$patientName}",
            route('medical-reports.show', $reportId)
        );
    }
}
