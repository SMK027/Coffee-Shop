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
            background: #ffffff;
        }

        .meta {
            margin-bottom: 5mm;
            font-size: 9px;
            color: #57534e;
            border-bottom: 0.4mm solid #d6d3d1;
            padding-bottom: 2mm;
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
            border: 0.5mm solid #a8a29e;
            border-radius: 2.2mm;
            margin: 0 2.5mm 2.5mm 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 10px;
            overflow: hidden;
            page-break-inside: avoid;
            background: #ffffff;
        }

        .card-header {
            background: #78350f;
            color: #ffffff;
            padding: 2.5mm 2.8mm 2.2mm 2.8mm;
            border-bottom: 1.2mm solid #f59e0b;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-size: 6.5px;
            color: #fcd34d;
            margin: 0 0 0.8mm 0;
        }

        .title {
            font-size: 11px;
            font-weight: bold;
            margin: 0;
        }

        .card-body {
            padding: 2.5mm 2.8mm 2mm 2.8mm;
        }

        .details {
            border-left: 0.8mm solid #f59e0b;
            padding-left: 2mm;
            min-height: 12mm;
        }

        .muted {
            font-size: 7.5px;
            color: #57534e;
            margin: 0 0 0.9mm 0;
            line-height: 1.25;
        }

        .label {
            color: #a16207;
            font-size: 6.5px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .qr {
            text-align: center;
            margin: 2.2mm auto 1.8mm auto;
            padding: 1.8mm;
            width: 34mm;
            height: 34mm;
            box-sizing: border-box;
            border: 0.4mm solid #d6d3d1;
            background: #fafaf9;
        }

        .qr svg {
            width: 30mm;
            height: 30mm;
        }

        .qr img {
            width: 30mm;
            height: 30mm;
            display: inline-block;
        }

        .number {
            font-family: monospace;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: center;
            color: #78350f;
            margin: 1mm 0 0 0;
            word-break: break-all;
        }

        .footer {
            margin-top: 2mm;
            padding-top: 1.5mm;
            border-top: 0.3mm solid #e7e5e4;
            font-size: 6.5px;
            color: #78716c;
            line-height: 1.3;
            text-align: center;
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
                <div class="card-header">
                    <p class="eyebrow">Le Coffee Shop</p>
                    <p class="title">Badge supervision</p>
                </div>

                <div class="card-body">
                    <div class="details">
                        <p class="muted"><span class="label">Responsable</span><br>{{ $card['owner_name'] }}</p>
                        <p class="muted"><span class="label">Détenteur</span><br>{{ $card['holder_label'] }}</p>
                        @if($card['position_label'] !== '')
                            <p class="muted"><span class="label">Poste</span><br>{{ $card['position_label'] }}</p>
                        @endif
                    </div>

                    <div class="qr"><img src="{{ $card['qr_data_uri'] }}" alt="QR superviseur"></div>

                    <p class="number">{{ $card['supervisor_number'] }}</p>

                    <div class="footer">
                        Scannez ce code pour autoriser une opération sensible.<br>
                        Usage interne — toute usurpation est interdite.
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
