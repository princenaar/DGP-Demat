<?php

namespace Database\Factories;

use App\Models\EtatDemande;
use App\Models\TypeDocument;
use App\Models\WorkflowTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowTransition>
 */
class WorkflowTransitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $source = EtatDemande::query()->firstOrCreate(['nom' => EtatDemande::EN_ATTENTE]);
        $cible = EtatDemande::query()->firstOrCreate(['nom' => EtatDemande::RECEPTIONNEE]);

        return [
            'type_document_id' => TypeDocument::create([
                'nom' => 'Document de test',
                'code' => fake()->unique()->bothify('TST##'),
            ])->id,
            'etat_source_id' => $source->id,
            'etat_cible_id' => $cible->id,
            'role_requis' => 'ADMIN',
            'automatique' => false,
            'ordre' => fake()->numberBetween(1, 10),
        ];
    }
}
