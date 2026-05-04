<?php

namespace Tests\Feature;

use App\Models\Demande;
use App\Models\EtatDemande;
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

class AccueilRoleTest extends TestCase
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

    public function test_accueil_can_list_show_and_receptionner_demande(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $this->actingAs($accueil)->get(route('demandes.index'))->assertOk();
        $this->actingAs($accueil)->get(route('demandes.show', $demande))->assertOk();

        $this->actingAs($accueil)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::RECEPTIONNEE,
            'commentaire' => 'Réception accueil',
        ])->assertRedirect(route('demandes.show', $demande));

        $this->assertSame(EtatDemande::RECEPTIONNEE, $demande->fresh()->etatDemande->nom);
    }

    public function test_accueil_cannot_access_settings_or_later_workflow_actions(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::RECEPTIONNEE);

        $this->actingAs($accueil)->get(route('settings.index'))->assertForbidden();

        $this->actingAs($accueil)->from(route('demandes.show', $demande))->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::VALIDEE,
            'commentaire' => 'Validation non autorisée',
        ])->assertForbidden();

        $this->assertSame(EtatDemande::RECEPTIONNEE, $demande->fresh()->etatDemande->nom);
    }

    private function makeDemande(string $etat): Demande
    {
        return Demande::create([
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
        ]);
    }
}
