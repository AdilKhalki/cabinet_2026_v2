<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $report->report_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            padding: 30px 35px 20px 35px;
        }

        .header { border-bottom: 2px solid #01a9ac; padding-bottom: 15px; margin-bottom: 20px; }
        .header-left { float: left; width: 60%; }
        .header-right { float: right; width: 38%; text-align: right; }
        .clearfix::after { content: ''; display: table; clear: both; }

        .doctor-name { font-size: 18px; font-weight: bold; color: #01a9ac; }
        .cabinet-info { font-size: 11px; color: #666; margin-top: 3px; line-height: 1.6; }

        .report-title {
            text-align: center;
            margin: 25px 0;
            padding: 12px;
            border: 2px solid #01a9ac;
            background: #f8fffe;
        }
        .report-title h2 {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #01a9ac;
            margin: 0;
        }
        .report-title h3 {
            font-size: 13px;
            color: #555;
            margin-top: 5px;
            font-weight: normal;
        }

        .patient-box {
            background: #f8f9fa;
            border-left: 3px solid #01a9ac;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .patient-box table { width: 100%; border-collapse: collapse; }
        .patient-box td { padding: 3px 8px; font-size: 11px; }
        .patient-box td.label-cell { font-weight: bold; color: #555; width: 22%; }

        .validity-box {
            border: 2px solid #e74c3c;
            padding: 10px 15px;
            margin: 15px 0;
            background: #fff5f5;
            font-size: 12px;
        }
        .validity-box strong { color: #e74c3c; }

        .content-section {
            margin: 20px 0;
            min-height: 200px;
            line-height: 1.9;
            text-align: justify;
            font-size: 12px;
        }

        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 15px; }
        .date-city { font-size: 11px; color: #666; }

        .signature-block {
            float: right;
            width: 45%;
            text-align: center;
        }
        .signature-label { font-size: 11px; color: #666; margin-bottom: 5px; }
        .signature-name { font-weight: bold; font-size: 12px; margin-top: 5px; }
        .signature-spec { font-size: 10px; color: #666; }

        .pdf-footer {
            position: fixed;
            bottom: 10px;
            left: 35px;
            right: 35px;
            text-align: center;
            font-size: 9px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    @php
        // Le médecin signataire du rapport prime sur les paramètres du cabinet (cabinet multi-médecins)
        $doc            = $report->doctor;
        $doctorName     = preg_replace('/^Dr\.?\s+/i', '', $doc?->name ?: ($settings['doctor_name'] ?? ''));
        $doctorSpec     = $doc?->speciality ?: ($settings['doctor_speciality'] ?? null);
        $doctorLicense  = $doc?->license_number ?: ($settings['doctor_license'] ?? null);
        $signaturePath  = $doc?->signature_path ? storage_path('app/public/' . $doc->signature_path) : null;
        $stampPath      = $doc?->stamp_path ? storage_path('app/public/' . $doc->stamp_path) : null;
        $signaturePath  = $signaturePath && is_file($signaturePath) ? $signaturePath : null;
        $stampPath      = $stampPath && is_file($stampPath) ? $stampPath : null;
    @endphp

    {{-- En-tête Médecin --}}
    <div class="header clearfix">
        <div class="header-left">
            <div class="doctor-name">Dr. {{ $doctorName }}</div>
            <div class="cabinet-info">
                @if($doctorSpec)
                    {{ $doctorSpec }}<br>
                @endif
                @if($doctorLicense)
                    INPE : {{ $doctorLicense }}<br>
                @endif
                {{ $settings['cabinet_name'] ?? 'Cabinet Médical' }}<br>
                @if(!empty($settings['cabinet_address']))
                    {{ $settings['cabinet_address'] }}<br>
                @endif
                @if(!empty($settings['cabinet_city']))
                    {{ $settings['cabinet_city'] }}<br>
                @endif
                @if(!empty($settings['cabinet_phone']))
                    Tél : {{ $settings['cabinet_phone'] }}
                @endif
            </div>
        </div>
        <div class="header-right">
            <div style="font-size: 11px; color: #666;">
                Réf : <strong>{{ $report->report_number }}</strong><br>
                Date : {{ $report->report_date ? $report->report_date->format('d/m/Y') : now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    {{-- Titre du rapport --}}
    @php
        $typeLabels = \App\Models\MedicalReport::TYPE_LABELS;
    @endphp
    <div class="report-title">
        <h2>{{ $typeLabels[$report->type] ?? 'DOCUMENT MÉDICAL' }}</h2>
        @if($report->title)
            <h3>{{ $report->title }}</h3>
        @endif
    </div>

    {{-- Informations Patient --}}
    <div class="patient-box">
        <table>
            <tr>
                <td class="label-cell">Patient :</td>
                <td><strong>{{ $patient->full_name }}</strong></td>
                <td class="label-cell">N° Dossier :</td>
                <td>{{ $patient->dossier_number }}</td>
            </tr>
            <tr>
                <td class="label-cell">Né(e) le :</td>
                <td>
                    {{ $patient->birth_date ? $patient->birth_date->format('d/m/Y') : '—' }}
                    @if($patient->age) ({{ $patient->age }} ans) @endif
                </td>
                <td class="label-cell">CIN :</td>
                <td>{{ $patient->cin ?? '—' }}</td>
            </tr>
            @if($patient->address || $patient->city)
            <tr>
                <td class="label-cell">Adresse :</td>
                <td colspan="3">{{ trim(($patient->address ?? '') . ' ' . ($patient->city ?? '')) }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Période d'arrêt de travail --}}
    @if($report->type === 'sick_leave' && ($report->valid_from || $report->valid_until))
        <div class="validity-box">
            <strong>Période d'arrêt de travail :</strong>
            Du {{ $report->valid_from ? $report->valid_from->format('d/m/Y') : '...' }}
            au {{ $report->valid_until ? $report->valid_until->format('d/m/Y') : '...' }}
            @if($report->valid_from && $report->valid_until)
                — soit {{ $report->valid_from->diffInDays($report->valid_until) + 1 }} jour(s)
            @endif
        </div>
    @endif

    {{-- Contenu du rapport --}}
    <div class="content-section">
        {!! nl2br(e($report->content)) !!}
    </div>

    {{-- Signature --}}
    <div class="footer clearfix">
        <div class="date-city">
            Fait à {{ $settings['cabinet_city'] ?? '____________' }}, le {{ $report->report_date ? $report->report_date->format('d/m/Y') : now()->format('d/m/Y') }}
        </div>
        <div class="signature-block">
            <div class="signature-label">Signature et cachet du médecin</div>
            @if($signaturePath)
                <img src="{{ $signaturePath }}" style="max-height: 60px;" alt="Signature">
            @else
                <div style="height: 60px; border-bottom: 1px solid #333; width: 150px; margin: 10px auto 5px;"></div>
            @endif
            @if($stampPath)
                <img src="{{ $stampPath }}" style="max-height: 80px;" alt="Cachet">
            @endif
            <div class="signature-name">Dr. {{ $doctorName }}</div>
            @if($doctorSpec)
                <div class="signature-spec">{{ $doctorSpec }}</div>
            @endif
        </div>
    </div>

    {{-- Pied de page --}}
    @if(!empty($settings['pdf_footer_text']))
        <div class="pdf-footer">
            {{ $settings['pdf_footer_text'] }}
        </div>
    @endif

</body>
</html>
