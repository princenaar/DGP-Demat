<?php

namespace Database\Seeders;

use App\Models\TypeDocument;
use Illuminate\Database\Seeder;

class TypeDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'nom' => 'Attestation de fonds de motivation',
                'code' => 'AFM',
                'champs_requis' => [
                    'categorie_socioprofessionnelle_id' => true,
                ],
                'eligibilite' => null,
                'description' => 'Demande liée aux fonds de motivation.',
                'icone' => 'banknotes',
            ],
            [
                'nom' => 'Attestation de travail',
                'code' => 'TRV',
                'champs_requis' => [
                    'date_prise_service' => true,
                ],
                'eligibilite' => null,
                'description' => 'Attestation confirmant la situation de travail actuelle.',
                'icone' => 'briefcase',
            ],
            [
                'nom' => 'Certificat administratif',
                'code' => 'ADM',
                'champs_requis' => [
                    'date_prise_service' => true,
                ],
                'eligibilite' => null,
                'description' => 'Certificat administratif de présence en service.',
                'icone' => 'document-text',
            ],
            [
                'nom' => 'Certificat de travail',
                'code' => 'CTRV',
                'champs_requis' => [
                    'date_prise_service' => true,
                    'date_fin_service' => true,
                ],
                'eligibilite' => null,
                'description' => 'Certificat couvrant une période de travail terminée.',
                'icone' => 'clipboard-document-check',
            ],
            [
                'nom' => 'Attestation de non activité dans la fonction publique',
                'code' => 'ANA',
                'champs_requis' => [
                    'date_depart_retraite' => true,
                ],
                'eligibilite' => 'etatique',
                'description' => 'Attestation réservée aux agents étatiques retraités.',
                'icone' => 'shield-check',
            ],
        ];

        foreach ($documents as $doc) {
            TypeDocument::updateOrCreate(
                ['code' => $doc['code']],
                $doc
            );
        }
    }
}
