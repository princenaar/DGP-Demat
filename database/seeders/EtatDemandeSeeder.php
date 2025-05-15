<?php

namespace Database\Seeders;

use App\Models\EtatDemande;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seed the application's database with EtatDemande.
 */

class EtatDemandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $etats = [
            EtatDemande::EN_ATTENTE,
            EtatDemande::RECEPTIONNEE,
            EtatDemande::VALIDEE,
            EtatDemande::REFUSEE,
            EtatDemande::COMPLEMENTS,
            EtatDemande::EN_SIGNATURE,
            EtatDemande::SUSPENDUE,
        ];

        foreach ($etats as $etat) {
            EtatDemande::firstOrCreate(['nom' => $etat]);
        }
    }
}
