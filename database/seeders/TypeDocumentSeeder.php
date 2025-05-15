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
                    'categorie_socioprofessionnelle' => true,
                ],
            ],
            [
                'nom' => 'Attestation de travail',
                'code' => 'TRV',
                'champs_requis' => [
                    'date_prise_service' => true,
                ],
            ],
            [
                'nom' => 'Certificat administratif',
                'code' => 'ADM',
                'champs_requis' => [
                    'date_prise_service' => true,
                ],
            ],
            [
                'nom' => 'Certificat de travail',
                'code' => 'CTRV',
                'champs_requis' => [
                    'date_prise_service' => true,
                    'date_fin_service' => true,
                ],
            ],
            [
                'nom' => 'Attestation de non activité dans la fonction publique',
                'code' => 'ANA',
                'champs_requis' => [
                    'date_depart_retraite' => true,
                ],
            ],
        ];

        foreach ($documents as $doc) {
            TypeDocument::firstOrCreate([
                'nom' => $doc['nom'],
                'code' => $doc['code'],
                'champs_requis' => $doc['champs_requis'],
            ]);
        }
    }
}
