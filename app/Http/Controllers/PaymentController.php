<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function pdf(Payment $payment)
    {
        Gate::authorize('view', $payment);

        $payment->load(['patient', 'doctor', 'lines.medicalAct']);

        $settings = [];
        if (Storage::exists('cabinet_settings.json')) {
            $settings = json_decode(Storage::get('cabinet_settings.json'), true) ?? [];
        }

        $amountInWords = self::amountInWords((float) $payment->total_amount);

        $pdf = Pdf::loadView('pdf.invoice', compact('payment', 'settings', 'amountInWords'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("facture-{$payment->invoice_number}.pdf");
    }

    /** Montant en toutes lettres (dirhams / centimes) — requiert l'extension intl */
    private static function amountInWords(float $amount): ?string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return null;
        }

        $formatter = new \NumberFormatter('fr', \NumberFormatter::SPELLOUT);
        $cents = (int) round($amount * 100);
        $dirhams = intdiv($cents, 100);
        $centimes = $cents % 100;

        $words = $formatter->format($dirhams).' dirham'.($dirhams > 1 ? 's' : '');
        if ($centimes > 0) {
            $words .= ' et '.$formatter->format($centimes).' centime'.($centimes > 1 ? 's' : '');
        }

        return ucfirst($words);
    }
}
