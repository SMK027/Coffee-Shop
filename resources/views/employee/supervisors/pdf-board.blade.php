<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planche superviseurs</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #292524;
            font-size: 10px;
        }

        .meta {
            margin-bottom: 6mm;
            font-size: 9px;
            color: #57534e;
        }

        .sheet {
            font-size: 0;
        }

        .card {
            display: inline-block;
            vertical-align: top;
            width: 50mm;
            max-width: 50mm;
            height: 85mm;
            max-height: 85mm;
            border: 0.4mm solid #d6d3d1;
            border-radius: 2mm;
            margin: 0 2.5mm 2.5mm 0;
            padding: 2.5mm;
            box-sizing: border-box;
            font-size: 10px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .title {
            font-size: 10px;
            font-weight: bold;
            margin: 0 0 1.5mm 0;
        }

        .muted {
            font-size: 8px;
            color: #57534e;
            margin: 0;
        }

        .qr {
            text-align: center;
            margin: 2.5mm 0;
        }

        .qr svg {
            width: 30mm;
            height: 30mm;
        }

        .number {
            font-family: monospace;
            font-size: 9px;
            margin: 1.5mm 0;
            word-break: break-all;
        }

        .footer {
            margin-top: 2.5mm;
            font-size: 7.5px;
            color: #78716c;
            line-height: 1.3;
        }
    </style>
</head>
<body>
    <div class="meta">
        Planche de supervision générée le {{ $generatedAt->format('d/m/Y H:i') }}
        @if(!empty($generatedBy))
            par {{ $generatedBy }}
        @endif
        — {{ $cards->count() }} carte(s)
    </div>

    <div class="sheet">
        @foreach($cards as $card)
            <div class="card">
                <p class="title">Supervision</p>
                <p class="muted">Responsable: {{ $card['owner_name'] }}</p>
                <p class="muted">Détenteur: {{ $card['holder_label'] }}</p>
                @if($card['position_label'] !== '')
                    <p class="muted">Poste: {{ $card['position_label'] }}</p>
                @endif

                <div class="qr">{!! $card['qr_svg'] !!}</div>

                <p class="number">{{ $card['supervisor_number'] }}</p>

                <div class="footer">
                    Usage strictement interne. Toute usurpation d'identité est passible de sanctions.
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
