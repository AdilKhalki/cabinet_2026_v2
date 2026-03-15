<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $report->report_number }} - {{ $report->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #222;
            background: #fff;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 20mm 25mm 20mm;
            position: relative;
        }

        /* Header */
        .header {
            border-bottom: 2px solid #01a9ac;
            padding-bottom: 12px;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .header-left {
            float: left;
            width: 60%;
        }
        .header-right {
            float: right;
            width: 38%;
            text-align: right;
        }
        .cabinet-name {
            font-size: 16pt;
            font-weight: bold;
            color: #01a9ac;
            margin-bottom: 4px;
        }
        .doctor-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .doctor-info {
            font-size: 9pt;
            color: #555;
            line-height: 1.5;
        }
        .doc-date {
            font-size: 10pt;
            color: #555;
            margin-top: 8px;
        }
        .doc-number {
            font-size: 9pt;
            color: #888;
        }

        /* Type badge */
        .report-type-banner {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #ccc;
            background: #f8f8f8;
        }
        .report-type-banner h2 {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #333;
        }
        .report-type-banner h3 {
            font-size: 12pt;
            color: #555;
            margin-top: 4px;
        }

        /* Patient box */
        .patient-box {
            border: 1px solid #ddd;
            padding: 12px 16px;
            margin-bottom: 20px;
            background: #fafafa;
            border-radius: 3px;
        }
        .patient-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .patient-box td {
            padding: 3px 8px;
            font-size: 10pt;
        }
        .patient-box td:first-child {
            font-weight: bold;
            width: 28%;
            color: #555;
        }

        /* Content */
        .content-section {
            margin: 20px 0;
            min-height: 150mm;
        }
        .content-section p {
            line-height: 1.8;
            text-align: justify;
        }

        /* Footer / Signature */
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 12px;
        }
        .signature-area {
            float: right;
            width: 50%;
            text-align: center;
        }
        .signature-label {
            font-size: 10pt;
            color: #555;
            margin-bottom: 4px;
        }
        .made-at {
            font-size: 10pt;
            color: #555;
        }
        .stamp-area {
            height: 80px;
            border: 1px dashed #ccc;
            margin: 10px auto;
            width: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #bbb;
            font-size: 9pt;
        }
        .doctor-sig {
            font-weight: bold;
            font-size: 11pt;
        }

        /* Validity (for arret de travail) */
        .validity-box {
            border: 2px solid #e74c3c;
            padding: 10px 14px;
            margin: 15px 0;
            border-radius: 3px;
            background: #fff5f5;
        }
        .validity-box strong {
            color: #e74c3c;
        }

        .clearfix::after { content: ''; display: table; clear: both; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header clearfix">
        <div class="header-left">
            <div class="cabinet-name">{{ $settings['cabinet_name'] ?? 'Cabinet Médical' }}</div>
            <div class="doctor-name">Dr. {{ $settings['doctor_name'] ?? ($report->doctor->name ?? '') }}</div>
            <div class="doctor-info">
                @if(!empty($settings['doctor_speciality']) || isset($report->doctor->speciality))
                    {{ $settings['doctor_speciality'] ?? $report->doctor->speciality }}<br>
                @endif
                @if(!empty($settings['doctor_license']) || isset($report->doctor->license_number))
                    Inpe : {{ $settings['doctor_license'] ?? $report->doctor->license_number }}<br>
                @endif
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
            <div class="doc-date">
                Le {{ now()->format('d/m/Y') }}
            </div>
            <div class="doc-number" style="margin-top:6px;">
                Réf : {{ $report->report_number }}
            </div>
        </div>
    </div>

    {{-- Type de rapport --}}
    <div class="report-type-banner">
        @php
            $typeLabels = [
                'certificat'    => 'CERTIFICAT MÉDICAL',
                'arret_travail' => "CERTIFICAT D'ARRÊT DE TRAVAIL",
                'compte_rendu'  => 'COMPTE-RENDU MÉDICAL',
                'autre'         => 'DOCUMENT MÉDICAL',
            ];
        @endphp
        <h2>{{ $typeLabels[$report->report_type] ?? strtoupper($report->report_type) }}</h2>
        @if($report->title && $report->title !== ($typeLabels[$report->report_type] ?? ''))
            <h3>{{ $report->title }}</h3>
        @endif
    </div>

    {{-- Patient --}}
    <div class="patient-box">
        <table>
            <tr>
                <td>Patient :</td>
                <td><strong>{{ $patient->full_name }}</strong></td>
                <td>N° Dossier :</td>
                <td>{{ $patient->dossier_number }}</td>
            </tr>
            <tr>
                <td>Né(e) le :</td>
                <td>{{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->format('d/m/Y') : '-' }}
                    @if($patient->age) ({{ $patient->age }} ans) @endif
                </td>
                <td>CIN :</td>
                <td>{{ $patient->cin ?? '-' }}</td>
            </tr>
            @if($patient->address || $patient->city)
            <tr>
                <td>Adresse :</td>
                <td colspan="3">{{ trim(($patient->address ?? '') . ' ' . ($patient->city ?? '')) }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Période d'arrêt (pour arrêt de travail) --}}
    @if($report->report_type === 'arret_travail' && ($report->valid_from || $report->valid_until))
    <div class="validity-box">
        <strong>Période d'arrêt de travail :</strong>
        Du {{ $report->valid_from ? \Carbon\Carbon::parse($report->valid_from)->format('d/m/Y') : '...' }}
        au {{ $report->valid_until ? \Carbon\Carbon::parse($report->valid_until)->format('d/m/Y') : '...' }}
        @if($report->valid_from && $report->valid_until)
            ({{ \Carbon\Carbon::parse($report->valid_from)->diffInDays(\Carbon\Carbon::parse($report->valid_until)) + 1 }} jour(s))
        @endif
    </div>
    @endif

    {{-- Contenu --}}
    <div class="content-section">
        {!! nl2br(e($report->content)) !!}
    </div>

    {{-- Footer / Signature --}}
    <div class="footer clearfix">
        <div class="made-at">
            Fait à {{ $settings['cabinet_city'] ?? '' }}, le {{ now()->format('d/m/Y') }}
        </div>
        <div class="signature-area">
            <div class="signature-label">Signature et cachet du médecin</div>
            <div class="stamp-area">Cachet</div>
            <div class="doctor-sig">Dr. {{ $settings['doctor_name'] ?? ($report->doctor->name ?? '') }}</div>
            @if(!empty($settings['doctor_speciality']))
                <div style="font-size:9pt;color:#666;">{{ $settings['doctor_speciality'] }}</div>
            @endif
        </div>
    </div>

    {{-- Footer text --}}
    @if(!empty($settings['pdf_footer_text']))
    <div style="position:absolute;bottom:15mm;left:20mm;right:20mm;text-align:center;font-size:8pt;color:#aaa;border-top:1px solid #eee;padding-top:6px;">
        {{ $settings['pdf_footer_text'] }}
    </div>
    @endif

</div>
</body>
</html>
