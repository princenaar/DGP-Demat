<?php

namespace Database\Seeders;

use App\Models\Structure;
use Illuminate\Database\Seeder;

class StructureSeeder extends Seeder
{
    public function run(): void
    {
        $structures = [
            'Direction des Ressources Humaines',
            'Centre Hospitalier National Universitaire de Fann',
            'Centre de Santé de Pikine',
            'District Sanitaire de Thiès',
            'Hôpital Dalal Jamm',
            'Direction de la Prévention',
            'Centre de Santé de Ziguinchor',
            'Hôpital Régional de Kaolack',
            'District Sanitaire de Louga',
            'Direction de la Santé de la Reproduction',
        ];

        foreach ($structures as $nom) {
            Structure::firstOrCreate(['nom' => $nom]);
        }
    }
}
