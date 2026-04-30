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

class DashboardTest extends TestCase
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

    public function test_dashboard_renders_aggregates_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $this->makeDemande(EtatDemande::EN_ATTENTE);
        $this->makeDemande(EtatDemande::RECEPTIONNEE);
        $signee = $this->makeDemande(EtatDemande::SIGNEE, ['created_at' => now()->subHours(4)]);
        HistoriqueEtat::create([
            'demande_id' => $signee->id,
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::SIGNEE)->value('id'),
            'user_id' => $admin->id,
            'commentaire' => 'Signature',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas('countsByEtat')
            ->assertViewHas('demandesATraiterCount', 1)
            ->assertViewHas('countsByTypeLast30Days')
            ->assertViewHas('averageSignatureTime')
            ->assertSee('Demandes par état')
            ->assertSee('Mes demandes à traiter');
    }

    public function test_dashboard_data_returns_only_rows_awaiting_current_user_action(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $otherAgent = User::factory()->create();
        $otherAgent->assignRole('AGENT');
        $visible = $this->makeDemande(EtatDemande::VALIDEE, [
            'agent_id' => $agent->id,
            'nom' => 'Visible',
        ]);
        $this->makeDemande(EtatDemande::VALIDEE, [
            'agent_id' => $otherAgent->id,
            'nom' => 'Cache',
        ]);
        $this->makeDemande(EtatDemande::EN_SIGNATURE, [
            'agent_id' => $agent->id,
            'nom' => 'PasActionAgent',
        ]);

        $response = $this->actingAs($agent)->getJson(route('dashboard.data'));

        $response->assertOk();
        $payload = $response->json('data');

        $this->assertCount(1, $payload);
        $this->assertSame($visible->nom, $payload[0]['nom']);
    }

    public function test_chef_dashboard_sees_receptionnee_demande_to_process(): void
    {
        $chef = User::factory()->create();
        $chef->assignRole('CHEF_DE_DIVISION');
        $this->makeDemande(EtatDemande::RECEPTIONNEE, ['nom' => 'A traiter']);
        $this->makeDemande(EtatDemande::EN_ATTENTE, ['nom' => 'Pas encore']);

        $response = $this->actingAs($chef)->getJson(route('dashboard.data'));

        $response->assertOk();
        $payload = $response->json('data');

        $this->assertCount(1, $payload);
        $this->assertSame('A traiter', $payload[0]['nom']);
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
