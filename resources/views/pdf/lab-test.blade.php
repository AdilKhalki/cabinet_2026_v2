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

        .patient-box {
            background: #f8f9fa;
            border-left: 3px solid #01a9ac;
            padding: 10px;
            margin-bottom: 15px;
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

        .info-line {
            font-size: 11px;
            color: #555;
            margin-bottom: 3px;
        }

        table.params {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.params th {
            background: #01a9ac;
            color: #fff;
            padding: 6px 8px;
            font-size: 8px;
            text-align: left;
            border: 1px solid #019a9c;
        }

        table.params td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 8px;
        }

        table.params tr:nth-child(even) {
            background: #f8f9fa;
        }

        .clinical-box {
            margin-top: 15px;
            padding: 10px;
            background: #fffbea;
            border-left: 3px solid #f4a100;
        }

        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .date-city {
            font-size: 11px;
            color: #666;
        }
    </style>
</head>

<body style="padding-top:140px;">
    {{-- En-tête Médecin --}}
    {{-- <div class="header clearfix">
        <div class="header-left">
            <div class="doctor-name">Dr. {{ $labTest->doctor->name }}</div>
            @if ($labTest->doctor->speciality)
                <div class="cabinet-info">{{ $labTest->doctor->speciality }}</div>
            @endif
            @if ($labTest->doctor->license_number)
                <div class="cabinet-info">N° RPPS: {{ $labTest->doctor->license_number }}</div>
            @endif
            <div class="cabinet-info">Cabinet Médical — CabiMed</div>
        </div>
        <div class="header-right">
            <div style="font-size: 11px; color: #666;">
                Demande N°: <strong>{{ $labTest->request_number }}</strong><br>
                Date: {{ $labTest->requested_at->format('d/m/Y') }}
            </div>
        </div>
    </div> --}}

    {{-- Patient --}}
    <table style="width:100%;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div class="patient-box">
                    <strong>Patient(e) :</strong> {{ $labTest->patient->full_name }}<br>
                    <span style="font-size:9px;">
                        Âge: {{ $labTest->patient->age }} ans
                        @if ($labTest->patient->blood_group)
                            — Groupe sanguin: {{ $labTest->patient->blood_group }}
                        @endif
                        <br>
                        Dossier: {{ $labTest->patient->dossier_number }}
                    </span>
                </div>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div class="patient-box">
                    <div style="font-size: 11px; color: #666;">
                        Demande N°: <strong>{{ $labTest->request_number }}</strong><br>
                        Date: {{ $labTest->requested_at->format('d/m/Y') }}
                        <br>
                        .
                    </div>
                </div>
            </td>
        </tr>
    </table>


    {{-- Titre --}}
    <div class="title">DEMANDE D'ANALYSES BIOLOGIQUES</div>
    

    {{-- Laboratoire --}}
    @if ($labTest->laboratory_name)
        <div class="info-line"><strong>Laboratoire :</strong> {{ $labTest->laboratory_name }}</div>
    @endif

    {{-- Tableau des paramètres demandés --}}
    @if ($labTest->results->count())
        <table class="params">
            <thead>
                <tr>
                    <th style="width: 5%;">N°</th>
                    <th style="width: 45%;">Paramètre demandé</th>
                    <th style="width: 25%;">Valeurs normales</th>
                    <th style="width: 25%;">Résultat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($labTest->results as $result)
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $result->parameter_name }}</strong>
                            @if ($result->notes)
                                <br><em style="color: #888; font-size: 10px;">{{ $result->notes }}</em>
                            @endif
                        </td>
                        <td style="font-size: 8px; color: #666;">
                            @if ($result->normal_min !== null && $result->normal_max !== null)
                                {{ $result->normal_min }} - {{ $result->normal_max }}
                                @if ($result->unit)
                                    {{ $result->unit }}
                                @endif
                            @elseif($result->unit)
                                {{ $result->unit }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="border: 1px dashed #aaa;">
                            {{-- Zone vide pour que le labo écrive le résultat --}}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #999; margin-top: 20px;">Aucun paramètre spécifié.</p>
    @endif

    {{-- Renseignements cliniques --}}
    @if ($labTest->clinical_info)
        <div class="clinical-box">
            <strong>Renseignements cliniques :</strong><br>
            {{ $labTest->clinical_info }}
        </div>
    @endif

    {{-- Signature et cachet --}}
    {{-- <div class="footer">
        <div class="clearfix">
            <div class="date-city" style="float: left;">
                Fait à ____________, le {{ $labTest->requested_at->format('d/m/Y') }}
            </div>
            <div style="float: right; text-align: center;">
                <p style="font-size: 11px; color: #666;">Signature & Cachet du médecin</p>
                @if ($labTest->doctor->signature_path)
                    <img src="{{ storage_path('app/public/' . $labTest->doctor->signature_path) }}"
                         style="max-height: 60px;" alt="Signature">
                @else
                    <div style="height: 60px; border-bottom: 1px solid #333; width: 150px; margin-top: 10px;"></div>
                @endif
            </div>
        </div>
    </div> --}}
</body>

</html>
