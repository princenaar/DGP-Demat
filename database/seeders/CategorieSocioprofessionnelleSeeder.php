<?php

namespace Database\Seeders;

use App\Models\CategorieSocioprofessionnelle;
use Illuminate\Database\Seeder;

class CategorieSocioprofessionnelleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['libelle' => 'Médecin', 'code' => 'MEDECIN'],
            ['libelle' => 'Infirmier', 'code' => 'INFIRMIER'],
            ['libelle' => 'Sage-femme', 'code' => 'SAGE_FEMME'],
            ['libelle' => 'Administratif', 'code' => 'ADMINISTRATIF'],
            ['libelle' => 'Technicien', 'code' => 'TECHNICIEN'],
            ['libelle' => 'Ouvrier', 'code' => 'OUVRIER'],
            ['libelle' => 'Autre', 'code' => 'AUTRE'],
        ];

        foreach ($categories as $index => $categorie) {
            CategorieSocioprofessionnelle::updateOrCreate(
                ['libelle' => $categorie['libelle']],
                [
                    'code' => $categorie['code'],
                    'ordre' => $index + 1,
                ]
            );
        }
    }
}
