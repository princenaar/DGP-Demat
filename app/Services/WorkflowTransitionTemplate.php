<?php

namespace App\Services;

use App\Models\EtatDemande;
use App\Models\TypeDocument;
use App\Models\WorkflowTransition;

class WorkflowTransitionTemplate
{
    /**
     * @return list<array{source: string, cible: string, role: string}>
     */
    public function transitions(): array
    {
        return [
            ['source' => EtatDemande::EN_ATTENTE, 'cible' => EtatDemande::RECEPTIONNEE, 'role' => 'ACCUEIL'],
            ['source' => EtatDemande::RECEPTIONNEE, 'cible' => EtatDemande::VALIDEE, 'role' => 'CHEF_DE_DIVISION'],
            ['source' => EtatDemande::RECEPTIONNEE, 'cible' => EtatDemande::REFUSEE, 'role' => 'CHEF_DE_DIVISION'],
            ['source' => EtatDemande::VALIDEE, 'cible' => EtatDemande::COMPLEMENTS, 'role' => 'AGENT'],
            ['source' => EtatDemande::VALIDEE, 'cible' => EtatDemande::EN_SIGNATURE, 'role' => 'AGENT'],
            ['source' => EtatDemande::EN_SIGNATURE, 'cible' => EtatDemande::SIGNEE, 'role' => 'DRH'],
            ['source' => EtatDemande::EN_SIGNATURE, 'cible' => EtatDemande::SUSPENDUE, 'role' => 'DRH'],
        ];
    }

    public function createFor(TypeDocument $typeDocument): void
    {
        $etats = EtatDemande::query()->pluck('id', 'nom');

        foreach ($this->transitions() as $index => $transition) {
            $workflowTransition = WorkflowTransition::firstOrNew([
                'type_document_id' => $typeDocument->id,
                'etat_source_id' => $etats[$transition['source']],
                'etat_cible_id' => $etats[$transition['cible']],
            ]);

            $workflowTransition->role_requis = $transition['role'];
            $workflowTransition->ordre = $index + 1;

            if (! $workflowTransition->exists) {
                $workflowTransition->automatique = false;
            }

            $workflowTransition->save();
        }
    }
}
