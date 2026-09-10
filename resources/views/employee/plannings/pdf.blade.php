<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning</title>
    <style>
        @page {
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #292524;
            font-size: 11px;
            background: #ffffff;
        }

        .meta {
            margin-bottom: 3mm;
            font-size: 9.5px;
            color: #57534e;
            border-bottom: 0.4mm solid #d6d3d1;
            padding-bottom: 2mm;
        }

        .header {
            margin-bottom: 4mm;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 1.5mm 0;
            color: #78350f;
        }

        .header p {
            margin: 0;
            font-size: 11px;
            color: #57534e;
        }

        .legend {
            margin-bottom: 5mm;
            font-size: 9px;
            color: #57534e;
        }

        .legend .chip {
            display: inline-block;
            margin: 0 3mm 0 0;
            padding: 0.8mm 2.2mm;
        }

        .employee-block {
            margin-bottom: 8mm;
            page-break-inside: avoid;
        }

        .employee-name {
            font-size: 13px;
            font-weight: bold;
            color: #78350f;
            background: #fef3c7;
            padding: 2mm 3mm;
            border-radius: 1.5mm;
            margin-bottom: 2mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        tbody tr {
            page-break-inside: avoid;
        }

        th, td {
            border: 0.3mm solid #d6d3d1;
            padding: 2.2mm 2.6mm;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f5f5f4;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #57534e;
        }

        .day-name {
            font-weight: bold;
            font-size: 10.5px;
        }

        .day-date {
            font-size: 9px;
            color: #78716c;
        }

        .empty {
            color: #a8a29e;
            font-style: italic;
        }

        .muted-total {
            font-size: 9.5px;
            color: #78716c;
            margin: 0 0 2mm 0;
        }

        .chip {
            border-radius: 1.2mm;
            border-width: 0.3mm;
            border-style: solid;
            padding: 1.2mm 1.8mm;
            margin-bottom: 1.2mm;
        }

        .chip-time {
            font-weight: bold;
            font-size: 10px;
        }

        .chip-label {
            font-weight: bold;
            font-size: 9.5px;
        }

        .chip-title {
            font-size: 9px;
            margin-top: 0.5mm;
        }

        .chip-work {
            background: #fffbeb;
            border-color: #fde68a;
            color: #78350f;
        }

        .chip-meeting {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
        }

        .chip-leave {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .chip-absence {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        /* Tableau de service : une ligne par salarié, plus facile à lire d'un coup d'œil. */
        .team-board .employee-col {
            width: 15%;
            font-weight: bold;
            color: #78350f;
            background: #fafaf9;
        }

        .team-board .total-col {
            width: 9%;
            font-weight: bold;
            white-space: nowrap;
        }

        .team-board tbody tr:nth-child(even) td {
            background: #fafaf9;
        }

        .team-board tbody tr:nth-child(even) td.employee-col {
            background: #f5f5f4;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        @if($title === 'Planning individuel' && $schedules->count() === 1)
            <p>{{ $schedules->first()['employee']->name }}</p>
        @endif
        <p>Semaine du {{ $weekStart->format('d/m/Y') }} au {{ $weekEnd->format('d/m/Y') }}</p>
    </div>

    <div class="meta">
        Document généré le {{ $generatedAt->format('d/m/Y H:i') }}<br>
        Émis par le superviseur #{{ $issuedBySupervisor->supervisor_number }}
        @php($issuerName = $issuedBySupervisor->holderAdmin?->name ?? $issuedBySupervisor->superadmin?->name)
        @if($issuerName)
            ({{ $issuerName }})
        @endif
    </div>

    <div class="legend">
        <span class="chip chip-work">Travail</span>
        <span class="chip chip-meeting">Réunion / formation</span>
        <span class="chip chip-leave">Congé</span>
        <span class="chip chip-absence">Absence</span>
    </div>

    @if($title === 'Planning individuel')
        @foreach($schedules as $schedule)
            <div class="employee-block">
                <p class="muted-total">Total travaillé : {{ $schedule['totalWorkedLabel'] }}</p>

                <table>
                    <thead>
                        <tr>
                            @foreach($days as $day)
                                <th>
                                    <span class="day-name">{{ ucfirst($day->translatedFormat('l')) }}</span><br>
                                    <span class="day-date">{{ $day->format('d/m') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($days as $day)
                                <td>
                                    @forelse($schedule['events']->get($day->toDateString(), collect()) as $shift)
                                        @include('employee.plannings.pdf-event', ['shift' => $shift])
                                    @empty
                                        <span class="empty">—</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <table class="team-board">
            <thead>
                <tr>
                    <th class="employee-col">Salarié</th>
                    @foreach($days as $day)
                        <th>
                            <span class="day-name">{{ ucfirst($day->translatedFormat('l')) }}</span><br>
                            <span class="day-date">{{ $day->format('d/m') }}</span>
                        </th>
                    @endforeach
                    <th class="total-col">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($schedules as $schedule)
                    <tr>
                        <td class="employee-col">{{ $schedule['employee']->name }}</td>
                        @foreach($days as $day)
                            <td>
                                @forelse($schedule['events']->get($day->toDateString(), collect()) as $shift)
                                    @include('employee.plannings.pdf-event', ['shift' => $shift])
                                @empty
                                    <span class="empty">—</span>
                                @endforelse
                            </td>
                        @endforeach
                        <td class="total-col">{{ $schedule['totalWorkedLabel'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
