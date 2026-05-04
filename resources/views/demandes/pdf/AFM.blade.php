@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATTESTATION</title>
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

        .numero {
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
    </style>
</head>
<body>
<div class="page">
    <div class="numero">N° <b>{{ sprintf("%05d", $demande->id) }}</b> MSHP/DRH/DGP/cald</div>
    <div class="numero" style="margin-top: 20px;">Dakar, le {{ Carbon::now()->format('d/m/Y') }}</div>
    <div class="title">ATTESTATION</div>

    <div class="content">
        <p>
            Je soussignée, Monsieur le Directeur des Ressources humaines, atteste que Mme/M.
            <strong>{{ $demande->prenom }}</strong> <strong>{{ strtoupper($demande->nom) }}</strong>,
            {{ $demande->categorieSocioprofessionnelle?->libelle }}, @if($demande->statut === 'contractuel')
                contractuel,
            @elseif($demande->statut === 'étatique')
                matricule de solde n° <strong>{{ $demande->matricule }},</strong>
            @endif
            est bénéficiaire du fonds de motivation de la santé pour un montant qui s’élève à cent cinquante mille
            (150 000) francs CFA par trimestre.
        </p>
        <p>
            En foi de quoi, la présente attestation est établie pour servir et valoir ce que de droit.
        </p>
        <!-- afficher qrCode si disponible -->
        @if($demande->code_qr)
            <div class="footer">
                <!-- QR Code -->
                <div class="qr-code">
                    <img src="data:image/png;base64, {{ base64_encode($qrCode) }}" class="qr-code-img" alt="QR Code">
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
