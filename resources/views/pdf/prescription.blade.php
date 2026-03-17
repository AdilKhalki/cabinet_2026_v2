<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            padding: 30px 35px 20px 35px;
        }

        .header {
            border-bottom: 2px solid #01a9ac;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-left {
            float: left;
        }

        .header-right {
            float: right;
            text-align: right;
        }

        .clearfix::after {
            content: '';
            display: table;
            clear: both;
        }

        .doctor-name {
            font-size: 18px;
            font-weight: bold;
            color: #01a9ac;
        }

        .cabinet-info {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .stamp {
            width: 80px;
            height: 80px;
            border: 2px solid #01a9ac;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 9px;
            color: #01a9ac;
            padding: 5px;
        }

        .patient-box {
            background: #f8f9fa;
            border-left: 3px solid #01a9ac;
            padding: 10px;
            margin-bottom: 15px;
        }

        .rx-symbol {
            font-size: 40px;
            color: #01a9ac;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .medication-line {
            border-bottom: 1px dashed #ddd;
            padding: 4px 0;
            margin-bottom: 5px;
        }

        .medication-name {
            font-weight: bold;
            font-size: 9px;
        }

        .medication-detail {
            color: #555;
            font-size: 8px;
            margin-top: 3px;
        }

        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .signature-area {
            margin-top: 20px;
        }

        .date-city {
            font-size: 11px;
            color: #666;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            color: #01a9ac;
            text-align: center;
            margin-bottom: 15px;
            padding: 8px;
            border: 1px solid #01a9ac;
        }
    </style>
</head>

<body style="padding-top:140px;">
    {{-- En-tête --}}
    {{-- <div class="header clearfix">
        <div class="header-left">
            <div class="doctor-name">Dr. {{ $prescription->doctor->name }}</div>
            @if ($prescription->doctor->speciality)
                <div class="cabinet-info">{{ $prescription->doctor->speciality }}</div>
            @endif
            @if ($prescription->doctor->license_number)
                <div class="cabinet-info">N° RPPS: {{ $prescription->doctor->license_number }}</div>
            @endif
            <div class="cabinet-info">Cabinet Médical — {{ config('app.name', 'MISAHATI') }}</div>
        </div>
        <div class="header-right">
            <div style="font-size: 11px; color: #666;">
                Ordonnance N°: <strong>{{ $prescription->prescription_number }}</strong><br>
                Le: {{ $prescription->prescribed_at->format('d/m/Y') }}<br>
                @if ($prescription->valid_until)
                    Valable jusqu'au: {{ $prescription->valid_until->format('d/m/Y') }}
                @endif
            </div>
        </div>
    </div> --}}

    {{-- Patient --}}
    <table style="width:100%;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div class="patient-box">
                    <strong>Patient(e) :</strong> {{ $prescription->patient->full_name }}<br>
                    <span style="font-size:11px;">
                        Âge: {{ $prescription->patient->age }} ans
                        @if ($prescription->patient->blood_group)
                            — Groupe sanguin: {{ $prescription->patient->blood_group }}
                        @endif
                        Dossier: {{ $prescription->patient->dossier_number }}
                    </span>
                </div>

            </td>
            <td style="width:50%; vertical-align:top;">
                <div class="patient-box">
                    <div style="font-size: 11px; color: #666;">
                        Ordonnance N°: <strong>{{ $prescription->prescription_number }}</strong><br>
                        Le: {{ $prescription->prescribed_at->format('d/m/Y') }}<br>
                        @if ($prescription->valid_until)
                            Valable jusqu'au: {{ $prescription->valid_until->format('d/m/Y') }}
                        @endif
                    </div>
                </div>

            </td>
        </tr>
    </table>


    {{-- Rx --}}
    {{-- <div class="rx-symbol">℞</div> --}}
    {{-- Titre --}}
    <div class="title">ORDONNANCE</div>

    {{-- Médicaments --}}
    @foreach ($prescription->lines as $line)
        <div class="medication-line">
            <div class="medication-name">{{ $loop->iteration }}. {{ $line->medication_name }}</div>
            <div class="medication-detail">
                @if ($line->dosage)
                    <strong>Posologie :</strong> {{ $line->dosage }}<br>
                @endif
                @if ($line->frequency)
                    <strong>Fréquence :</strong> {{ $line->frequency }}<br>
                @endif
                @if ($line->duration_days)
                    <strong>Durée :</strong> {{ $line->duration_days }} jours<br>
                @endif
                @if ($line->route)
                    <strong>Voie :</strong> {{ $line->route }}<br>
                @endif
                @if ($line->instructions)
                    <em>{{ $line->instructions }}</em>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Notes générales --}}
    @if ($prescription->notes)
        <div style="margin-top: 15px; padding: 10px; background: #fffbea; border-left: 3px solid #f4a100;">
            <strong>Instructions :</strong> {{ $prescription->notes }}
        </div>
    @endif

    {{-- Signature et cachet --}}
    {{-- <div class="footer">
        <div class="clearfix">
            <div class="date-city" style="float: left;">
                Fait à ____________, le {{ $prescription->prescribed_at->format('d/m/Y') }}
            </div>
            <div style="float: right; text-align: center;">
                <p style="font-size: 11px; color: #666;">Signature & Cachet du médecin</p>
                @if ($prescription->doctor->signature_path)
                    <img src="{{ storage_path('app/public/' . $prescription->doctor->signature_path) }}"
                        style="max-height: 60px;" alt="Signature">
                @else
                    <div style="height: 60px; border-bottom: 1px solid #333; width: 150px; margin-top: 10px;"></div>
                @endif
            </div>
        </div>
    </div> --}}
</body>

</html>
