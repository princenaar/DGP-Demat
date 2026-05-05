@php use Carbon\Carbon; Carbon::setLocale('fr'); @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        @font-face {
            font-family: 'Tahoma';
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/tahoma.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'Tahoma';
            font-style: normal;
            font-weight: bold;
            src: url('{{ storage_path('fonts/tahoma-bold.ttf') }}') format('truetype');
        }

        body {
            margin: 0;
            padding: 0;
            background-image: url('{{ public_path('storage/images/background.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .page {
            position: relative;
            padding: 2cm;
            box-sizing: border-box;
        }

        .title {
            font-family: Tahoma, serif;
            text-align: center;
            font-size: 24pt;
            font-weight: bold;
            padding: 10px;
            background-color: #ccc;
            border: 1px solid #aaa;
            margin-bottom: 30px;
            margin-top: 150px;
        }

        .content {
            font-family: Tahoma, serif;
            font-size: 12pt;
            line-height: 1.6;
            margin-top: 50px;
            text-align: justify;
        }

        .numero, .date {
            text-align: right;
        }

        .footer {
            font-size: 0; /* Supprime l'espace entre inline-block */
        }

        .qr-code, .signature {
            text-align: center;
            display: inline-block;
            width: 50%;
            vertical-align: top; /* Alignement en haut */
            box-sizing: border-box;
            font-size: 12pt; /* Rétablit la taille de police */
        }

        .qr-box {
            display: inline-block;
            width: 210px;
            border: 1px solid #333;
            padding: 10px 12px;
            text-align: center;
            background: rgba(255, 255, 255, 0.82);
        }

        .qr-box img {
            display: block;
            margin: 0 auto 6px;
        }

        .qr-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .qr-value {
            margin-top: 3px;
            font-family: "Courier New", monospace;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .qr-instructions {
            margin-top: 6px;
            font-size: 7.5pt;
            line-height: 1.25;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="numero">{!! $demande->reference_document !!}</div>
    <div class="date" style="margin-top: 20px;">Dakar, le {{ Carbon::now()->format('d/m/Y') }}</div>
    <div class="title">@yield('title')</div>

    <div class="content">
        @yield('content')
        <!-- afficher qrCode si disponible -->
        @if($demande->verification_code && $qrCode)
            <div class="footer">
                <!-- QR Code -->
                <div class="qr-code">
                    <div class="qr-box">
                        <img src="data:image/png;base64, {{ base64_encode($qrCode) }}" class="qr-code-img" alt="QR Code">
                        <div class="qr-label">Code de vérification</div>
                        <div class="qr-value">{{ $demande->verification_code }}</div>
                        <div class="qr-instructions">
                            Scannez ce QR code ou saisissez ce code sur la page d’accueil pour vérifier l’authenticité de cet acte.
                        </div>
                    </div>
                </div>

                <!-- Signature -->
                <div class="signature">
                    <img src="{{ public_path('storage/images/signature.png') }}" alt="Signature" width="200">
                </div>
            </div>
        @endif

    </div>
</div>
</body>
</html>
