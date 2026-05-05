<?php

namespace Tests\Feature;

use App\Mail\DemandeComplementMail;
use App\Mail\DemandeSigneeMail;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\FichierJustificatif;
use App\Models\PieceRequise;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\CategorieSocioprofessionnelleSeeder;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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
            CategorieSocioprofessionnelleSeeder::class,
            WorkflowTransitionSeeder::class,
        ]);

        config([
            'services.recaptcha.site_key' => 'test-site-key',
            'services.recaptcha.secret_key' => 'test-secret-key',
        ]);
    }

    public function test_public_create_page_renders_reference_data(): void
    {
        $structure = Structure::firstOrFail();

        $response = $this->get(route('demandes.create'));

        $response
            ->assertOk()
            ->assertViewIs('demandes.create')
            ->assertViewHas('types')
            ->assertViewHas('structures')
            ->assertViewHas('categoriesSocioprofessionnelles')
            ->assertViewHas('recaptchaSiteKey')
            ->assertSee('g-recaptcha', false)
            ->assertSee('<select name="structure_id"', false)
            ->assertSee('<option value="'.$structure->id.'"', false)
            ->assertSee($structure->nom)
            ->assertDontSee('Sélection :')
            ->assertSee('Rechercher une structure')
            ->assertSee('Ajouter des pièces justificatives')
            ->assertSee('Faire une demande');
    }

    public function test_public_user_can_store_demande_with_supporting_file(): void
    {
        Mail::fake();
        Storage::fake('local');
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'étatique',
            'nin' => '1234567890123',
            'matricule' => '123456A',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
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

    public function test_demande_numbers_are_sequential_by_type_and_year(): void
    {
        $afm = TypeDocument::where('code', 'AFM')->firstOrFail();
        $trv = TypeDocument::where('code', 'TRV')->firstOrFail();

        $premiereAfm = $this->makeDemande(EtatDemande::EN_ATTENTE, ['type_document_id' => $afm->id]);
        $deuxiemeAfm = $this->makeDemande(EtatDemande::EN_ATTENTE, ['type_document_id' => $afm->id]);
        $premiereTrv = $this->makeDemande(EtatDemande::EN_ATTENTE, ['type_document_id' => $trv->id]);
        $annee = now()->format('Y');

        $this->assertSame("AFM-{$annee}00001", $premiereAfm->numero_demande);
        $this->assertSame("AFM-{$annee}00002", $deuxiemeAfm->numero_demande);
        $this->assertSame("TRV-{$annee}00001", $premiereTrv->numero_demande);
    }

    public function test_pdf_reference_uses_business_number_and_agent_initials(): void
    {
        $agent = User::factory()->create([
            'name' => 'Nom ignoré',
            'initial' => 'ad',
        ]);
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agent->id]);

        $html = view('demandes.pdf.TRV', [
            'demande' => $demande->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => null,
        ])->render();

        $this->assertStringContainsString('N° <b>TRV-'.now()->format('Y').'00001</b> MSHP/DRH/DGP/AD', $html);
        $this->assertStringNotContainsString('cald', $html);
    }

    public function test_pdf_reference_falls_back_to_agent_name_initials(): void
    {
        $agent = User::factory()->create(['name' => 'Elimane Traoré']);
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agent->id]);

        $this->assertSame('ET', $demande->initiales_agent);
    }

    public function test_signed_pdf_displays_framed_qr_with_verification_code_and_instructions(): void
    {
        $demande = $this->makeDemande(EtatDemande::SIGNEE, [
            'code_qr' => 'ancien-jeton',
            'verification_code' => 'ABCD-2345',
        ]);

        $html = view('demandes.pdf.TRV', [
            'demande' => $demande->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => 'qr-content',
        ])->render();

        $this->assertStringContainsString('class="qr-box"', $html);
        $this->assertStringContainsString('Code de vérification', $html);
        $this->assertStringContainsString('ABCD-2345', $html);
        $this->assertStringContainsString('Scannez ce QR code ou saisissez ce code sur la page d’accueil pour vérifier l’authenticité de cet acte.', $html);
    }

    public function test_non_engagement_pdf_uses_short_title(): void
    {
        $ana = TypeDocument::where('code', 'ANA')->firstOrFail();
        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $ana->id,
            'date_depart_retraite' => '2024-01-15',
        ]);

        $html = view('demandes.pdf.ANA', [
            'demande' => $demande->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => null,
        ])->render();

        $this->assertStringContainsString('ATTESTATION DE NON ENGAGEMENT', $html);
        $this->assertStringNotContainsString('ATTESTATION DE NON ACTIVITE DANS LA FONCTION PUBLIQUE', $html);
    }

    public function test_etatique_demande_requires_matricule(): void
    {
        $type = TypeDocument::firstOrFail();
        $structure = Structure::firstOrFail();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'étatique',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors('matricule');
    }

    public function test_store_demande_validates_masks(): void
    {
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();
        Http::preventStrayRequests();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'étatique',
            'nin' => '123',
            'matricule' => 'MAT-001',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '771234567',
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'invalid-token',
        ]);

        $response->assertSessionHasErrors([
            'nin',
            'matricule',
            'telephone',
        ]);
    }

    public function test_store_demande_rejects_failed_recaptcha_verification(): void
    {
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
        ]);

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'étatique',
            'nin' => '1234567890123',
            'matricule' => '123456A',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'invalid-token',
        ]);

        $response->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_store_demande_does_not_call_recaptcha_when_local_validation_fails(): void
    {
        Http::preventStrayRequests();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => 'invalid',
            'nom' => '',
            'prenom' => '',
            'statut' => 'étatique',
            'nin' => '123',
            'matricule' => 'MAT-001',
            'structure_id' => 'invalid',
            'email' => 'invalid-email',
            'telephone' => '771234567',
            'g-recaptcha-response' => 'token-that-should-not-be-sent',
        ]);

        $response->assertSessionHasErrors([
            'type_document_id',
            'nom',
            'prenom',
            'nin',
            'matricule',
            'structure_id',
            'email',
            'telephone',
        ]);
    }

    public function test_store_demande_handles_unavailable_recaptcha_service(): void
    {
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => fn () => throw new ConnectionException('Timeout'),
        ]);

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_store_demande_fails_when_recaptcha_secret_is_missing(): void
    {
        config(['services.recaptcha.secret_key' => null]);

        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_store_demande_rejects_statut_that_does_not_match_type_eligibility(): void
    {
        $type = TypeDocument::where('code', 'ANA')->firstOrFail();
        $structure = Structure::firstOrFail();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'date_depart_retraite' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors('statut');
    }

    public function test_store_demande_requires_categorie_id_for_types_that_need_it(): void
    {
        $type = TypeDocument::where('code', 'AFM')->firstOrFail();
        $structure = Structure::firstOrFail();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors('categorie_socioprofessionnelle_id');
    }

    public function test_store_demande_requires_upload_when_type_has_required_pieces(): void
    {
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();
        PieceRequise::create([
            'type_document_id' => $type->id,
            'libelle' => 'Copie de la pièce d’identité',
            'obligatoire' => true,
            'ordre' => 1,
        ]);
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors('fichiers');
    }

    public function test_accueil_can_receptionner_demande_and_append_audit_comment(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $response = $this->actingAs($accueil)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::RECEPTIONNEE,
            'commentaire' => 'Dossier reçu',
        ]);

        $response
            ->assertRedirect(route('demandes.show', $demande))
            ->assertSessionHas('success');

        $demande->refresh();
        $this->assertSame(EtatDemande::RECEPTIONNEE, $demande->etatDemande->nom);
        $this->assertStringContainsString('Dossier reçu', $demande->commentaire);
        $this->assertStringContainsString($accueil->name, $demande->commentaire);
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
        $demande = $this->makeDemande(EtatDemande::SIGNEE, [
            'code_qr' => 'ancien-code-valide',
            'verification_code' => 'ABCD-2345',
        ]);

        $this->get(route('demandes.verifier', 'ABCD-2345'))
            ->assertOk()
            ->assertSee('Cette demande est authentique')
            ->assertSee($demande->numero_affiche);

        $this->get(route('demandes.verifier', 'ancien-code-valide'))
            ->assertOk()
            ->assertSee('Cette demande est authentique');

        $this->get(route('demandes.verifier', 'code-invalide'))
            ->assertOk()
            ->assertSee('Code de vérification invalide');
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

    public function test_demandes_index_renders_etat_filter(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)->get(route('demandes.index'))
            ->assertOk()
            ->assertViewHas('etatOptions')
            ->assertSee('Tous les états')
            ->assertSee('En attente');
    }

    public function test_data_endpoint_filters_rows_by_etat(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $visible = $this->makeDemande(EtatDemande::VALIDEE, ['nom' => 'Visible']);
        $hidden = $this->makeDemande(EtatDemande::RECEPTIONNEE, ['nom' => 'Cache']);
        $etatValideeId = EtatDemande::where('nom', EtatDemande::VALIDEE)->value('id');

        $response = $this->actingAs($admin)->getJson(route('demandes.data', [
            'etat_id' => $etatValideeId,
        ]));

        $response->assertOk();
        $payload = $response->json('data');

        $this->assertCount(1, $payload);
        $this->assertSame($visible->nom, $payload[0]['nom']);
        $this->assertNotSame($hidden->nom, $payload[0]['nom']);
    }

    public function test_data_endpoint_combines_etat_filter_with_agent_scope(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $otherAgent = User::factory()->create();
        $otherAgent->assignRole('AGENT');
        $visible = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agent->id, 'nom' => 'Visible']);
        $this->makeDemande(EtatDemande::RECEPTIONNEE, ['agent_id' => $agent->id, 'nom' => 'AutreEtat']);
        $hidden = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $otherAgent->id, 'nom' => 'Cache']);
        $etatValideeId = EtatDemande::where('nom', EtatDemande::VALIDEE)->value('id');

        $response = $this->actingAs($agent)->getJson(route('demandes.data', [
            'etat_id' => $etatValideeId,
        ]));

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
            ->withArgs(fn (string $view, array $data): bool => $view === 'demandes.pdf.TRV'
                && $data['demande']->numero_affiche === 'TRV-'.now()->format('Y').'00001'
                && $data['demande']->verification_code !== null)
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
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $demande->verification_code);
        $this->assertSame('demandes_signees/TRV-'.now()->format('Y').'00001.pdf', $demande->fichier_pdf);
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
