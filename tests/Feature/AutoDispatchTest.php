<?php

namespace Tests\Feature;

use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\HistoriqueEtat;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoDispatchTest extends TestCase
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

    public function test_default_agent_is_assigned_when_demande_is_receptionnee(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $type->update(['default_agent_id' => $agent->id]);
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE, ['type_document_id' => $type->id]);

        $this->actingAs($accueil)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::RECEPTIONNEE,
            'commentaire' => 'Réception du dossier',
        ])->assertRedirect(route('demandes.show', $demande));

        $demande->refresh();

        $this->assertSame(EtatDemande::RECEPTIONNEE, $demande->etatDemande->nom);
        $this->assertSame($agent->id, $demande->agent_id);
        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => $accueil->id,
        ]);
    }

    public function test_chef_can_manually_imputer_a_demande(): void
    {
        $chef = User::factory()->create();
        $chef->assignRole('CHEF_DE_DIVISION');
        $agent = User::factory()->create(['name' => 'Agent cible']);
        $agent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::RECEPTIONNEE);

        $this->actingAs($chef)->post(route('demandes.imputer', $demande), [
            'agent_id' => $agent->id,
            'commentaire' => 'Imputation prioritaire',
        ])->assertRedirect(route('demandes.show', $demande));

        $demande->refresh();

        $this->assertSame($agent->id, $demande->agent_id);
        $this->assertStringContainsString('Imputation prioritaire', $demande->commentaire);
        $this->assertSame(1, HistoriqueEtat::where('demande_id', $demande->id)->count());
    }

    public function test_show_page_keeps_agent_assignment_only_in_state_change_modal(): void
    {
        $chef = User::factory()->create();
        $chef->assignRole('CHEF_DE_DIVISION');
        $agent = User::factory()->create(['name' => 'Agent cible']);
        $agent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::RECEPTIONNEE);

        $this->actingAs($chef)->get(route('demandes.show', $demande))
            ->assertOk()
            ->assertSee('Valider la demande')
            ->assertSee('Imputer à un agent')
            ->assertSee('Agent cible')
            ->assertDontSee(route('demandes.imputer', $demande), false)
            ->assertDontSee('id="imputer_agent_id"', false);
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
