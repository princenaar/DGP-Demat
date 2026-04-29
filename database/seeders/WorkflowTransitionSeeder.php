<?php

namespace Database\Seeders;

use App\Models\EtatDemande;
use App\Models\TypeDocument;
use App\Models\WorkflowTransition;
use Illuminate\Database\Seeder;

class WorkflowTransitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $etats = EtatDemande::query()->pluck('id', 'nom');

        $transitions = [
            [EtatDemande::EN_ATTENTE, EtatDemande::RECEPTIONNEE, 'ADMIN'],
            [EtatDemande::RECEPTIONNEE, EtatDemande::VALIDEE, 'CHEF_DE_DIVISION'],
            [EtatDemande::RECEPTIONNEE, EtatDemande::REFUSEE, 'CHEF_DE_DIVISION'],
            [EtatDemande::VALIDEE, EtatDemande::COMPLEMENTS, 'AGENT'],
            [EtatDemande::VALIDEE, EtatDemande::EN_SIGNATURE, 'AGENT'],
            [EtatDemande::EN_SIGNATURE, EtatDemande::SIGNEE, 'DRH'],
            [EtatDemande::EN_SIGNATURE, EtatDemande::SUSPENDUE, 'DRH'],
        ];

        TypeDocument::query()
            ->whereIn('code', ['AFM', 'TRV', 'ADM', 'CTRV', 'ANA'])
            ->get()
            ->each(function (TypeDocument $typeDocument) use ($etats, $transitions): void {
                foreach ($transitions as $index => [$source, $cible, $role]) {
                    WorkflowTransition::updateOrCreate(
                        [
                            'type_document_id' => $typeDocument->id,
                            'etat_source_id' => $etats[$source],
                            'etat_cible_id' => $etats[$cible],
                        ],
                        [
                            'role_requis' => $role,
                            'automatique' => false,
                            'ordre' => $index + 1,
                        ]
                    );
                }
            });
    }
}
