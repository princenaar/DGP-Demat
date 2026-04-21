<?php

namespace Tests\Feature;

use App\Mail\DemandeComplementMail;
use App\Mail\DemandeSigneeMail;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\FichierJustificatif;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemandeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            EtatDemandeSeeder::class,
            TypeDocumentSeeder::class,
            StructureSeeder::class,
        ]);
    }

    public function test_public_create_page_renders_reference_data(): void
    {
        $response = $this->get(route('demandes.create'));

        $response
            ->assertOk()
            ->assertViewIs('demandes.create')
            ->assertViewHas('types')
            ->assertViewHas('structures')
            ->assertSee('Faire une demande');
    }

    public function test_public_user_can_store_demande_with_supporting_file(): void
    {
        Mail::fake();
        Storage::fake('local');

        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'étatique',
            'nin' => '1234567890123',
            'matricule' => 'MAT-001',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '771234567',
            'date_prise_service' => '2024-01-15',
            'fichiers' => [
                UploadedFile::fake()->create('piece.pdf', 128, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('demandes', [
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
        ]);
        $this->assertDatabaseHas('fichier_justificatifs', [
            'nom' => 'piece.pdf',
        ]);

        $fichier = FichierJustificatif::firstOrFail();
        Storage::disk('local')->assertExists($fichier->chemin);
    }

    public function test_etatique_demande_requires_matricule(): void
    {
        $type = TypeDocument::firstOrFail();
        $structure = Structure::firstOrFail();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'étatique',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
        ]);

        $response->assertSessionHasErrors('matricule');
    }

    public function test_admin_can_receptionner_demande_and_append_audit_comment(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $response = $this->actingAs($admin)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::RECEPTIONNEE,
            'commentaire' => 'Dossier reçu',
        ]);

        $response
            ->assertRedirect(route('demandes.show', $demande))
            ->assertSessionHas('success');

        $demande->refresh();
        $this->assertSame(EtatDemande::RECEPTIONNEE, $demande->etatDemande->nom);
        $this->assertStringContainsString('Dossier reçu', $demande->commentaire);
        $this->assertStringContainsString($admin->name, $demande->commentaire);
    }

    public function test_invalid_state_transition_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $response = $this->actingAs($admin)->from(route('demandes.show', $demande))->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::SIGNEE,
            'commentaire' => 'Transition directe',
        ]);

        $response->assertRedirect(route('demandes.show', $demande));
        $response->assertSessionHasErrors();
        $this->assertSame(EtatDemande::EN_ATTENTE, $demande->fresh()->etatDemande->nom);
    }

    public function test_chef_de_division_can_validate_and_assign_to_agent(): void
    {
        $chef = User::factory()->create();
        $chef->assignRole('CHEF_DE_DIVISION');
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::RECEPTIONNEE);

        $this->actingAs($chef)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::VALIDEE,
            'commentaire' => 'A affecter',
            'agent_id' => $agent->id,
        ])->assertRedirect(route('demandes.show', $demande));

        $demande->refresh();
        $this->assertSame(EtatDemande::VALIDEE, $demande->etatDemande->nom);
        $this->assertSame($agent->id, $demande->agent_id);
    }

    public function test_only_assigned_agent_can_request_complements(): void
    {
        Mail::fake();

        $assignedAgent = User::factory()->create();
        $assignedAgent->assignRole('AGENT');
        $otherAgent = User::factory()->create();
        $otherAgent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $assignedAgent->id]);

        $this->actingAs($otherAgent)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Pièces manquantes',
        ])->assertForbidden();

        $this->actingAs($assignedAgent)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Pièces manquantes',
        ])->assertRedirect(route('demandes.show', $demande));

        $this->assertSame(EtatDemande::COMPLEMENTS, $demande->fresh()->etatDemande->nom);
        Mail::assertSent(DemandeComplementMail::class);
    }

    public function test_signed_edit_link_only_allows_complement_state(): void
    {
        $complement = $this->makeDemande(EtatDemande::COMPLEMENTS);
        $validee = $this->makeDemande(EtatDemande::VALIDEE);

        $this->get(\URL::signedRoute('demandes.edit', ['demande' => $complement->id]))
            ->assertOk()
            ->assertViewIs('demandes.edit');

        $this->get(\URL::signedRoute('demandes.edit', ['demande' => $validee->id]))
            ->assertForbidden();
    }

    public function test_update_complemented_demande_returns_it_to_validated_state(): void
    {
        Storage::fake('local');
        $demande = $this->makeDemande(EtatDemande::COMPLEMENTS, ['nom' => 'Ancien']);
        $structure = Structure::firstOrFail();

        $response = $this->put(route('demandes.update'), [
            'id' => $demande->id,
            'nom' => 'Nouveau',
            'prenom' => $demande->prenom,
            'statut' => 'contractuel',
            'nin' => $demande->nin,
            'structure_id' => $structure->id,
            'telephone' => '771111111',
            'fichiers' => [
                UploadedFile::fake()->create('complement.pdf', 64, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('success');

        $demande->refresh();
        $this->assertSame('Nouveau', $demande->nom);
        $this->assertSame(EtatDemande::VALIDEE, $demande->etatDemande->nom);
        $this->assertDatabaseHas('fichier_justificatifs', ['nom' => 'complement.pdf']);
    }

    public function test_verification_page_distinguishes_valid_and_invalid_codes(): void
    {
        $demande = $this->makeDemande(EtatDemande::SIGNEE, ['code_qr' => 'code-valide']);

        $this->get(route('demandes.verifier', 'code-valide'))
            ->assertOk()
            ->assertSee('Cette demande est authentique');

        $this->get(route('demandes.verifier', 'code-invalide'))
            ->assertOk()
            ->assertSee('Code QR invalide');
    }

    public function test_data_endpoint_filters_agent_rows(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $otherAgent = User::factory()->create();
        $otherAgent->assignRole('AGENT');
        $visible = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agent->id, 'nom' => 'Visible']);
        $hidden = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $otherAgent->id, 'nom' => 'Cache']);

        $response = $this->actingAs($agent)->getJson(route('demandes.data'));

        $response->assertOk();
        $payload = $response->json('data');
        $this->assertCount(1, $payload);
        $this->assertSame($visible->nom, $payload[0]['nom']);
        $this->assertNotSame($hidden->nom, $payload[0]['nom']);
    }

    public function test_pdf_signature_transition_persists_file_and_sends_mail(): void
    {
        Mail::fake();
        Storage::fake('local');
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('A4')->andReturnSelf();
        $pdf->shouldReceive('output')->once()->andReturn('pdf-content');

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($pdf);

        $drh = User::factory()->create();
        $drh->assignRole('DRH');
        $demande = $this->makeDemande(EtatDemande::EN_SIGNATURE);

        $this->actingAs($drh)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::SIGNEE,
            'commentaire' => 'Signature validée',
        ])->assertRedirect(route('demandes.show', $demande));

        $demande->refresh();
        $this->assertSame(EtatDemande::SIGNEE, $demande->etatDemande->nom);
        $this->assertNotNull($demande->code_qr);
        $this->assertNotNull($demande->fichier_pdf);
        Storage::disk('local')->assertExists($demande->fichier_pdf);
        Mail::assertSent(DemandeSigneeMail::class);
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
