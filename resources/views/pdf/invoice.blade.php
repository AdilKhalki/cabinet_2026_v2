<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; padding: 30px 35px 20px 35px; }
        .header { background: #353c4e; color: white; padding: 20px; margin-bottom: 20px; }
        .header-title { font-size: 24px; font-weight: bold; color: #01a9ac; }
        .header-subtitle { font-size: 12px; color: #aaa; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #353c4e; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .total-row { background: #f8f9fa; font-weight: bold; }
        .grand-total { background: #01a9ac; color: white; }
        .badge-paid { background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; }
        .badge-pending { background: #ffc107; color: #333; padding: 3px 8px; border-radius: 3px; }
        .clearfix::after { content: ''; display: table; clear: both; }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div style="float:left;">
            <div class="header-title">FACTURE</div>
            <div class="header-subtitle">{{ $payment->invoice_number }}</div>
        </div>
        <div style="float:right; text-align:right; color: #ccc; font-size: 11px;">
            Cabinet Médical — {{ config('app.name', 'MISAHATI') }}<br>
            Dr. {{ $payment->doctor->name }}<br>
            Date: {{ $payment->payment_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}
        </div>
    </div>

    {{-- Infos patient --}}
    <div style="margin-bottom: 20px;">
        <strong>Patient :</strong> {{ $payment->patient->full_name }}<br>
        <strong>Dossier :</strong> {{ $payment->patient->dossier_number }}<br>
        <strong>Téléphone :</strong> {{ $payment->patient->phone }}<br>
        @if($payment->patient->insurance_name)
            <strong>Mutuelle :</strong> {{ $payment->patient->insurance_name }}
            @if($payment->patient->insurance_number)({{ $payment->patient->insurance_number }})@endif
        @endif
    </div>

    {{-- Lignes de facturation --}}
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:center;">Qté</th>
                <th style="text-align:right;">Prix Unit.</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payment->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td style="text-align:center;">{{ $line->quantity }}</td>
                    <td style="text-align:right;">{{ number_format($line->unit_price, 2) }} MAD</td>
                    <td style="text-align:right;">{{ number_format($line->total_price, 2) }} MAD</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">Sous-total</td>
                <td style="text-align:right;">{{ number_format($payment->subtotal, 2) }} MAD</td>
            </tr>
            @if($payment->discount > 0)
                <tr>
                    <td colspan="3">Remise</td>
                    <td style="text-align:right;">- {{ number_format($payment->discount, 2) }} MAD</td>
                </tr>
            @endif
            @if($payment->insurance_coverage > 0)
                <tr>
                    <td colspan="3">Part assurance</td>
                    <td style="text-align:right;">- {{ number_format($payment->insurance_coverage, 2) }} MAD</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td colspan="3"><strong>TOTAL À PAYER</strong></td>
                <td style="text-align:right;"><strong>{{ number_format($payment->total_amount, 2) }} MAD</strong></td>
            </tr>
            <tr>
                <td colspan="3">Montant payé</td>
                <td style="text-align:right; color: green;">{{ number_format($payment->amount_paid, 2) }} MAD</td>
            </tr>
            <tr class="{{ $payment->amount_due > 0 ? '' : 'total-row' }}">
                <td colspan="3"><strong>Reste à payer</strong></td>
                <td style="text-align:right; color: {{ $payment->amount_due > 0 ? 'red' : 'green' }};">
                    <strong>{{ number_format($payment->amount_due, 2) }} MAD</strong>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Statut --}}
    <div style="text-align:right; margin-top: 10px;">
        <span class="{{ $payment->status === 'paid' ? 'badge-paid' : 'badge-pending' }}">
            {{ \App\Models\Payment::STATUS_LABELS[$payment->status] ?? $payment->status }}
        </span>
    </div>

    @if($payment->notes)
        <div style="margin-top: 15px; color: #666; font-size: 11px;">
            <strong>Notes :</strong> {{ $payment->notes }}
        </div>
    @endif

    <div style="margin-top: 40px; border-top: 1px solid #ddd; padding-top: 15px; font-size: 10px; color: #999; text-align: center;">
        Merci de votre confiance — Cabinet Médical MISAHATI
    </div>
</body>
</html>
