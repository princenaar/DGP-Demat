<?php

namespace Tests\Feature;

use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Services\WorkflowEngine;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiDefaultAgentsTest extends TestCase
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

    public function test_multiple_default_agents_share_validated_demande_until_first_action_claims_it(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $agentA = User::factory()->create(['name' => 'Agent A']);
        $agentA->assignRole('AGENT');
        $agentB = User::factory()->create(['name' => 'Agent B']);
        $agentB->assignRole('AGENT');
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $type->defaultAgents()->sync([$agentA->id, $agentB->id]);
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE, ['type_document_id' => $type->id]);

        $this->actingAs($accueil)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::RECEPTIONNEE,
            'commentaire' => 'Réception du dossier',
        ])->assertRedirect(route('demandes.show', $demande));

        $demande->refresh();

        $this->assertSame(EtatDemande::VALIDEE, $demande->etatDemande->nom);
        $this->assertNull($demande->agent_id);
        $this->assertTrue(app(WorkflowEngine::class)->peut(
            $demande,
            EtatDemande::where('nom', EtatDemande::COMPLEMENTS)->firstOrFail(),
            $agentA
        ));

        $this->actingAs($agentA)->getJson(route('dashboard.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($agentB)->getJson(route('dashboard.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($agentA)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Pièce manquante',
        ])->assertRedirect(route('demandes.show', $demande));

        $demande->refresh();

        $this->assertSame($agentA->id, $demande->agent_id);
        $this->assertSame(EtatDemande::COMPLEMENTS, $demande->etatDemande->nom);
        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'user_id' => $agentA->id,
            'commentaire' => 'Demande prise en charge par Agent A.',
        ]);

        $claimedDemande = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $type->id,
            'agent_id' => $agentA->id,
        ]);

        $this->actingAs($agentB)->post(route('demandes.changerEtat', $claimedDemande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Tentative concurrente',
        ])->assertForbidden();
    }

    public function test_migration_backfills_legacy_default_agent_into_pivot_table(): void
    {
        Schema::drop('type_document_default_agents');

        $agent = User::factory()->create();
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $type->forceFill(['default_agent_id' => $agent->id])->save();

        $migration = require database_path('migrations/2026_05_16_191810_create_type_document_default_agents_table.php');
        $migration->up();

        $this->assertDatabaseHas('type_document_default_agents', [
            'type_document_id' => $type->id,
            'user_id' => $agent->id,
        ]);
    }

    public function test_inactive_default_agent_is_excluded_from_shared_queue(): void
    {
        $activeAgent = User::factory()->create();
        $activeAgent->assignRole('AGENT');
        $inactiveAgent = User::factory()->create([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);
        $inactiveAgent->assignRole('AGENT');
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $type->defaultAgents()->sync([$activeAgent->id, $inactiveAgent->id]);
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['type_document_id' => $type->id]);

        $this->actingAs($activeAgent)->getJson(route('dashboard.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($inactiveAgent)->getJson(route('dashboard.data'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
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
