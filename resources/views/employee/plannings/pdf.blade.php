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
            font-size: 10px;
            background: #ffffff;
        }

        .meta {
            margin-bottom: 5mm;
            font-size: 9px;
            color: #57534e;
            border-bottom: 0.4mm solid #d6d3d1;
            padding-bottom: 2mm;
        }

        .header {
            margin-bottom: 6mm;
        }

        .header h1 {
            font-size: 16px;
            margin: 0 0 1.5mm 0;
            color: #78350f;
        }

        .header p {
            margin: 0;
            font-size: 10px;
            color: #57534e;
        }

        .employee-block {
            margin-bottom: 8mm;
            page-break-inside: avoid;
        }

        .employee-name {
            font-size: 12px;
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

        th, td {
            border: 0.3mm solid #d6d3d1;
            padding: 1.8mm 2.2mm;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f5f5f4;
            font-size: 8.5px;
            text-transform: uppercase;
            color: #57534e;
        }

        .day-name {
            font-weight: bold;
            font-size: 9px;
        }

        .day-date {
            font-size: 8px;
            color: #78716c;
        }

        .event {
            margin-bottom: 1mm;
        }

        .event-time {
            font-weight: bold;
        }

        .empty {
            color: #a8a29e;
            font-style: italic;
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
        Document généré le {{ $generatedAt->format('d/m/Y H:i') }}
    </div>

    @foreach($schedules as $schedule)
        <div class="employee-block">
            @if($title !== 'Planning individuel')
                <p class="employee-name">{{ $schedule['employee']->name }}</p>
            @endif

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
                                    <div class="event">
                                        <span class="event-time">{{ substr($shift->start_time, 0, 5) }}-{{ substr($shift->end_time, 0, 5) }}</span>
                                        @if($shift->title)
                                            <br>{{ $shift->title }}
                                        @endif
                                    </div>
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
</body>
</html>
