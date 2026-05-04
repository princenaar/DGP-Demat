<?php

namespace Tests\Feature;

use App\Mail\DemandeComplementMail;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\WorkflowTransition;
use App\Services\WorkflowEngine;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            EtatDemandeSeeder::class,
            StructureSeeder::class,
            TypeDocumentSeeder::class,
            WorkflowTransitionSeeder::class,
        ]);
    }

    public function test_transitions_for_returns_outgoing_edges_for_current_type_and_state(): void
    {
        $demande = $this->makeDemande(EtatDemande::RECEPTIONNEE);
        $engine = app(WorkflowEngine::class);

        $transitions = $engine->transitionsFor($demande);

        $this->assertCount(2, $transitions);
        $this->assertEqualsCanonicalizing(
            [EtatDemande::VALIDEE, EtatDemande::REFUSEE],
            $transitions->pluck('etatCible.nom')->all()
        );
    }

    public function test_peut_checks_required_role_and_assigned_agent_guard(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $otherAgent = User::factory()->create();
        $otherAgent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agent->id]);
        $cible = EtatDemande::where('nom', EtatDemande::COMPLEMENTS)->firstOrFail();
        $engine = app(WorkflowEngine::class);

        $this->assertTrue($engine->peut($demande, $cible, $agent));
        $this->assertFalse($engine->peut($demande, $cible, $otherAgent));
    }

    public function test_transitionner_applies_side_effects_and_history(): void
    {
        Mail::fake();
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agent->id]);
        $cible = EtatDemande::where('nom', EtatDemande::COMPLEMENTS)->firstOrFail();

        app(WorkflowEngine::class)->transitionner($demande, $cible, $agent, [
            'commentaire' => 'Pièces manquantes',
        ]);

        $demande->refresh();

        $this->assertSame(EtatDemande::COMPLEMENTS, $demande->etatDemande->nom);
        $this->assertStringContainsString('Pièces manquantes', $demande->commentaire);
        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'etat_demande_id' => $cible->id,
            'user_id' => $agent->id,
        ]);
        Mail::assertSent(DemandeComplementMail::class);
    }

    public function test_automatic_validation_transition_runs_when_enabled_and_rules_pass(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE, [
            'type_document_id' => $type->id,
            'date_prise_service' => '2024-01-15',
        ]);

        WorkflowTransition::where('type_document_id', $type->id)
            ->whereBelongsTo(EtatDemande::where('nom', EtatDemande::RECEPTIONNEE)->firstOrFail(), 'etatSource')
            ->whereBelongsTo(EtatDemande::where('nom', EtatDemande::VALIDEE)->firstOrFail(), 'etatCible')
            ->update(['automatique' => true]);

        $this->actingAs($accueil)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::RECEPTIONNEE,
            'commentaire' => 'Réception',
        ])->assertRedirect(route('demandes.show', $demande));

        $this->assertSame(EtatDemande::VALIDEE, $demande->fresh()->etatDemande->nom);
    }

    private function makeDemande(string $etat, array $attributes = []): Demande
    {
        return Demande::create(array_merge([
            'type_document_id' => TypeDocument::where('code', 'TRV')->value('id'),
            'structure_id' => Structure::value('id'),
            'etat_demande_id' => EtatDemande::where('nom', $etat)->value('id'),
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa.diop@example.test',
            'telephone' => '771234567',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
            'date_prise_service' => '2024-01-15',
        ], $attributes));
    }
}
