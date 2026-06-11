<?php

namespace Tests\Feature;

use App\Mail\DemandeComplementMail;
use App\Mail\DemandeSigneeMail;
use App\Models\ApplicationSetting;
use App\Models\CategorieSocioprofessionnelle;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\FichierJustificatif;
use App\Models\PieceRequise;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Notifications\DemandeNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\CategorieSocioprofessionnelleSeeder;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
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

    public function test_public_create_page_only_exposes_external_status_for_ane(): void
    {
        $html = $this->get(route('demandes.create'))->getContent();

        $this->assertStringContainsString('x-show="isAne()"', $html);
        $this->assertStringContainsString('>Externe<', $html);
        $this->assertStringContainsString('x-bind:disabled="hasFixedEligibility()"', $html);
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
        $category = CategorieSocioprofessionnelle::firstOrFail();

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
            'categorie_socioprofessionnelle_id' => $category->id,
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

    public function test_public_store_for_type_with_default_agent_arrives_directly_validated(): void
    {
        Mail::fake();
        Storage::fake('local');
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $type->defaultAgents()->attach($agent);
        $structure = Structure::firstOrFail();
        $category = CategorieSocioprofessionnelle::firstOrFail();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Ndiaye',
            'prenom' => 'Moussa',
            'statut' => 'contractuel',
            'nin' => '1234567890124',
            'structure_id' => $structure->id,
            'email' => 'moussa.ndiaye@example.test',
            'telephone' => '+221 77 123 45 68',
            'categorie_socioprofessionnelle_id' => $category->id,
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('success');

        $demande = Demande::where('nin', '1234567890124')->firstOrFail();

        $this->assertSame(EtatDemande::VALIDEE, $demande->etatDemande->nom);
        $this->assertNull($demande->agent_id);
        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => null,
            'commentaire' => 'Validation automatique : agent(s) par défaut configuré(s).',
        ]);
    }

    public function test_public_user_cannot_store_new_demande_with_same_nin_when_active_demande_exists(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $activeDemande = $this->makeDemande(EtatDemande::EN_ATTENTE, [
            'nin' => '1234567890123',
            'matricule' => '123456A',
        ]);

        $response = $this->from(route('demandes.create'))->post(route('demandes.store'), $this->validStorePayload([
            'nin' => '1234567890123',
            'matricule' => '654321B',
        ]));

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHasErrors(['nin' => Demande::ACTIVE_DUPLICATE_MESSAGE]);

        $messages = implode(' ', session('errors')->get('nin'));

        $this->assertStringNotContainsString($activeDemande->numero_affiche, $messages);
        $this->assertDatabaseMissing('demandes', [
            'nin' => '1234567890123',
            'matricule' => '654321B',
        ]);
    }

    public function test_public_user_cannot_store_new_demande_with_same_matricule_when_active_demande_exists(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $this->makeDemande(EtatDemande::RECEPTIONNEE, [
            'nin' => '1234567890123',
            'matricule' => '123456A',
        ]);

        $response = $this->from(route('demandes.create'))->post(route('demandes.store'), $this->validStorePayload([
            'nin' => '9999999999999',
            'matricule' => '123456a',
        ]));

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHasErrors(['nin' => Demande::ACTIVE_DUPLICATE_MESSAGE]);

        $this->assertDatabaseMissing('demandes', [
            'nin' => '9999999999999',
        ]);
    }

    public function test_public_user_cannot_store_new_demande_with_same_email_when_active_demande_exists(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $activeDemande = $this->makeDemande(EtatDemande::VALIDEE, [
            'nin' => '1234567890123',
            'matricule' => '123456A',
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
        ]);

        $response = $this->from(route('demandes.create'))->post(route('demandes.store'), $this->validStorePayload([
            'nin' => '9999999999999',
            'matricule' => '654321B',
            'email' => ' AWA.DIOP@example.test ',
            'telephone' => '+221 76 987 65 43',
        ]));

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHasErrors(['nin' => Demande::ACTIVE_DUPLICATE_MESSAGE]);

        $messages = implode(' ', session('errors')->get('nin'));

        $this->assertStringNotContainsString($activeDemande->numero_affiche, $messages);
        $this->assertDatabaseMissing('demandes', [
            'nin' => '9999999999999',
        ]);
    }

    public function test_public_user_cannot_store_new_demande_with_same_telephone_when_active_demande_exists(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $this->makeDemande(EtatDemande::VALIDEE, [
            'nin' => '1234567890123',
            'matricule' => '123456A',
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
        ]);

        $response = $this->from(route('demandes.create'))->post(route('demandes.store'), $this->validStorePayload([
            'nin' => '9999999999999',
            'matricule' => '654321B',
            'email' => 'fatou.ndiaye@example.test',
            'telephone' => '+221771234567',
        ]));

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHasErrors(['nin' => Demande::ACTIVE_DUPLICATE_MESSAGE]);

        $this->assertDatabaseMissing('demandes', [
            'nin' => '9999999999999',
        ]);
    }

    public function test_public_user_can_store_new_demande_when_existing_demande_is_signed(): void
    {
        Notification::fake();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $this->makeDemande(EtatDemande::SIGNEE, [
            'nin' => '1234567890123',
            'matricule' => '123456A',
        ]);

        $response = $this->post(route('demandes.store'), $this->validStorePayload([
            'nin' => '1234567890123',
            'matricule' => '123456A',
        ]));

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('demandes', [
            'nom' => 'Ndiaye',
            'nin' => '1234567890123',
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
        ]);
    }

    public function test_public_user_can_store_new_demande_when_existing_demande_is_suspended(): void
    {
        Notification::fake();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $this->makeDemande(EtatDemande::SUSPENDUE, [
            'nin' => '1234567890123',
            'matricule' => '123456A',
        ]);

        $response = $this->post(route('demandes.store'), $this->validStorePayload([
            'nin' => '1234567890123',
            'matricule' => '123456A',
        ]));

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('demandes', [
            'nom' => 'Ndiaye',
            'nin' => '1234567890123',
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
        ]);
    }

    public function test_public_user_keeps_demande_when_confirmation_email_fails(): void
    {
        Storage::fake('local');
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();
        $category = CategorieSocioprofessionnelle::firstOrFail();

        $exception = new TransportException('550 L-RF2 This recipient has been reported recently as out of storage space');

        Notification::shouldReceive('send')
            ->once()
            ->withArgs(fn (object $notifiable, object $notification): bool => $notification instanceof DemandeNotification)
            ->andThrow($exception);
        Log::shouldReceive('warning')
            ->once()
            ->with('Échec d’envoi mail.', \Mockery::on(
                fn (array $context): bool => $context['action'] === 'confirmation_demande'
                    && $context['email'] === 'awa.diop@example.test'
                    && str_contains($context['message'], '550 L-RF2')
            ));

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
            'categorie_socioprofessionnelle_id' => $category->id,
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
            'fichiers' => [
                UploadedFile::fake()->create('piece.pdf', 128, 'application/pdf'),
            ],
        ]);

        $numeroDemande = 'TRV-'.now()->format('Y').'00001';

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('success', "Votre demande a été enregistrée avec succès sous le numéro {$numeroDemande}.")
            ->assertSessionHas('warning')
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('demandes', [
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'numero_demande' => $numeroDemande,
        ]);
    }

    public function test_public_create_form_renders_nested_file_validation_errors(): void
    {
        $response = $this
            ->followingRedirects()
            ->from(route('demandes.create'))
            ->post(route('demandes.store'), $this->validStorePayload([
                'fichiers' => [
                    UploadedFile::fake()->create('piece.txt', 1, 'text/plain'),
                ],
            ]));

        $response
            ->assertOk()
            ->assertSee('Les fichiers doivent être au format PDF, JPG, JPEG ou PNG.');

        $this->assertDatabaseMissing('demandes', [
            'nom' => 'Ndiaye',
            'email' => 'fatou.ndiaye@example.test',
        ]);
    }

    public function test_public_user_input_is_preserved_when_storage_fails_before_commit(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();
        $category = CategorieSocioprofessionnelle::firstOrFail();
        $filesystem = \Mockery::mock(FilesystemFactory::class);

        $filesystem->shouldReceive('disk')
            ->once()
            ->with('local')
            ->andThrow(new RuntimeException('Stockage indisponible'));

        $this->app->instance(FilesystemFactory::class, $filesystem);

        $response = $this->from(route('demandes.create'))->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'étatique',
            'nin' => '1234567890123',
            'matricule' => '123456A',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'categorie_socioprofessionnelle_id' => $category->id,
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
            'fichiers' => [
                UploadedFile::fake()->create('piece.pdf', 128, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHasErrors('error')
            ->assertSessionHasInput([
                'type_document_id' => (string) $type->id,
                'nom' => 'Diop',
                'prenom' => 'Awa',
                'email' => 'awa.diop@example.test',
            ]);

        $this->assertDatabaseMissing('demandes', [
            'nom' => 'Diop',
            'prenom' => 'Awa',
        ]);
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
        $ane = TypeDocument::where('code', 'ANE')->firstOrFail();
        $category = CategorieSocioprofessionnelle::create([
            'libelle' => 'Infirmier breveté spécialisé',
            'code' => 'INFIRMIER_BREVETE_SPECIALISE',
            'ordre' => 998,
        ]);
        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $ane->id,
            'structure_id' => null,
            'statut' => 'externe',
            'date_depart_retraite' => null,
            'categorie_socioprofessionnelle_id' => $category->id,
            'date_naissance' => '1990-05-12',
            'lieu_naissance' => 'dakar plateau',
        ]);

        $html = view('demandes.pdf.ANE', [
            'demande' => $demande->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => null,
        ])->render();

        $this->assertStringContainsString('ATTESTATION DE NON ENGAGEMENT', $html);
        $this->assertStringContainsString('M./Mme&nbsp;Awa&nbsp;DIOP</strong></span>, Infirmier breveté spécialisé, <span class="nowrap">né(e) le&nbsp;12 mai 1990</span> à Dakar Plateau,', $html);
        $this->assertStringNotContainsString('Dakar Plateau ,', $html);
        $this->assertStringContainsString('n’est ni boursier(ère), ni contractuel(le)', $html);
        $this->assertStringNotContainsString('pension de retraite', $html);
        $this->assertStringNotContainsString('ATTESTATION DE NON ACTIVITE DANS LA FONCTION PUBLIQUE', $html);
    }

    public function test_motivation_fund_pdf_adds_annual_period_for_contractuels_only(): void
    {
        $afm = TypeDocument::where('code', 'AFM')->firstOrFail();
        $annualPeriod = 'pour la période du <span class="nowrap">1<sup>er</sup>&nbsp;janvier</span> au <span class="nowrap">31&nbsp;décembre&nbsp;'.now()->format('Y').'.</span>';

        $contractuel = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $afm->id,
            'statut' => 'contractuel',
        ]);
        $etatique = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $afm->id,
            'statut' => 'étatique',
            'matricule' => '123456A',
        ]);

        $contractuelHtml = view('demandes.pdf.AFM', [
            'demande' => $contractuel->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => null,
        ])->render();
        $etatiqueHtml = view('demandes.pdf.AFM', [
            'demande' => $etatique->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => null,
        ])->render();

        $this->assertStringContainsString($annualPeriod, $contractuelHtml);
        $this->assertStringNotContainsString($annualPeriod, $etatiqueHtml);
    }

    public function test_pdf_identity_keeps_sensitive_administrative_groups_together(): void
    {
        $category = CategorieSocioprofessionnelle::create([
            'libelle' => 'Ingénieur principal des services administratifs et financiers',
            'code' => 'INGENIEUR_PRINCIPAL_SERVICES_ADMINISTRATIFS_FINANCIERS',
            'ordre' => 999,
        ]);

        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'prenom' => 'aminata SOKHNA',
            'nom' => 'diop samb',
            'statut' => 'étatique',
            'matricule' => '123456A',
            'categorie_socioprofessionnelle_id' => $category->id,
            'date_prise_service' => '2024-01-01',
        ]);

        $html = view('demandes.pdf.TRV', [
            'demande' => $demande->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => null,
        ])->render();

        $this->assertStringContainsString('<p class="administrative-paragraph keep-together">', $html);
        $this->assertStringContainsString('<span class="nowrap"><strong>M./Mme&nbsp;Aminata Sokhna&nbsp;DIOP SAMB</strong></span>', $html);
        $this->assertStringNotContainsString('aminata SOKHNA&nbsp;diop samb', $html);
        $this->assertStringContainsString('Ingénieur principal des services administratifs et financiers', $html);
        $this->assertStringContainsString('matricule de solde <strong>n°&nbsp;123456A</strong>,', $html);
        $this->assertStringContainsString('<span class="nowrap">1er janvier 2024</span>', $html);
        $this->assertStringContainsString('<span class="nowrap">ce que de droit</span>', $html);
        $this->assertDoesNotMatchRegularExpression('/<strong>[^<]*[,.;:!?]\s*<\/strong>/', $html);
    }

    public function test_travail_pdf_places_category_immediately_after_name(): void
    {
        $category = CategorieSocioprofessionnelle::create([
            'libelle' => 'Sage-Femme maîtresse',
            'code' => 'SAGE_FEMME_MAITRESSE_TEST',
            'ordre' => 997,
        ]);
        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'categorie_socioprofessionnelle_id' => $category->id,
        ]);

        $html = view('demandes.pdf.TRV', [
            'demande' => $demande->load('agent', 'typeDocument', 'categorieSocioprofessionnelle'),
            'qrCode' => null,
        ])->render();

        $this->assertStringContainsString('M./Mme&nbsp;Awa&nbsp;DIOP</strong></span>, Sage-Femme maîtresse', $html);
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

        $this->get(route('demandes.create'))
            ->assertSee('id="validation-errors"', false)
            ->assertSee('tabindex="-1"', false)
            ->assertSee("document.getElementById('validation-errors')?.focus()", false);
    }

    public function test_store_demande_rejects_failed_recaptcha_verification(): void
    {
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $structure = Structure::firstOrFail();
        $category = CategorieSocioprofessionnelle::firstOrFail();
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
            'categorie_socioprofessionnelle_id' => $category->id,
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
        $category = CategorieSocioprofessionnelle::firstOrFail();
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
            'categorie_socioprofessionnelle_id' => $category->id,
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
        $category = CategorieSocioprofessionnelle::firstOrFail();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
            'structure_id' => $structure->id,
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'categorie_socioprofessionnelle_id' => $category->id,
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_store_demande_rejects_statut_that_does_not_match_type_eligibility(): void
    {
        $type = TypeDocument::where('code', 'ANE')->firstOrFail();
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

        $response->assertSessionHasErrors('statut');
    }

    public function test_store_ane_demande_accepts_external_without_structure_or_matricule(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $type = TypeDocument::where('code', 'ANE')->firstOrFail();
        $type->update([
            'champs_requis' => [
                'categorie_socioprofessionnelle_id' => false,
                'date_naissance' => true,
                'lieu_naissance' => true,
            ],
        ]);

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'externe',
            'nin' => '1234567890123',
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'date_naissance' => '1990-05-12',
            'lieu_naissance' => 'Dakar',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertRedirect(route('demandes.create'));

        $this->assertDatabaseHas('demandes', [
            'type_document_id' => $type->id,
            'statut' => 'externe',
            'matricule' => null,
            'structure_id' => null,
            'categorie_socioprofessionnelle_id' => null,
            'date_naissance' => '1990-05-12 00:00:00',
            'lieu_naissance' => 'Dakar',
        ]);

        $demande = Demande::whereBelongsTo($type)->firstOrFail();
        $this->assertStringStartsWith('ANE-', $demande->numero_demande);
    }

    public function test_store_ane_demande_requires_category_birth_date_and_birth_place(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $type = TypeDocument::where('code', 'ANE')->firstOrFail();

        $response = $this->post(route('demandes.store'), [
            'type_document_id' => $type->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'statut' => 'externe',
            'nin' => '1234567890123',
            'email' => 'awa.diop@example.test',
            'telephone' => '+221 77 123 45 67',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $response->assertSessionHasErrors([
            'categorie_socioprofessionnelle_id',
            'date_naissance',
            'lieu_naissance',
        ]);
    }

    public function test_store_travail_demande_requires_category(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

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

        $response->assertSessionHasErrors('categorie_socioprofessionnelle_id');
    }

    public function test_store_administratif_demande_requires_category(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $type = TypeDocument::where('code', 'ADM')->firstOrFail();
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

        $response->assertSessionHasErrors('categorie_socioprofessionnelle_id');
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

    public function test_state_change_modal_comment_is_optional(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $response = $this->actingAs($accueil)->get(route('demandes.show', $demande));

        $response
            ->assertOk()
            ->assertSee('Commentaire (optionnel)')
            ->assertDontSee('id="commentaire" x-model="commentaire" class="mt-1 block w-full rounded-md border-gray-300 focus:border-senegal-green focus:ring-senegal-green" rows="4" required', false);
    }

    public function test_state_change_modal_prevents_double_submission(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $response = $this->actingAs($accueil)->get(route('demandes.show', $demande));

        $response
            ->assertOk()
            ->assertSee('etatSubmitting: false', false)
            ->assertSee('x-on:submit="if (etatSubmitting) { $event.preventDefault(); } else { etatSubmitting = true; }"', false)
            ->assertSee('x-bind:disabled="etatSubmitting"', false);
    }

    public function test_state_change_modal_shows_blocking_loading_overlay(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $response = $this->actingAs($accueil)->get(route('demandes.show', $demande));

        $response
            ->assertOk()
            ->assertSee('x-show="etatSubmitting"', false)
            ->assertSee('aria-busy="true"', false)
            ->assertSee('animate-spin', false)
            ->assertSee('Traitement en cours...');
    }

    public function test_demande_show_displays_missing_required_ane_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $ane = TypeDocument::where('code', 'ANE')->firstOrFail();
        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $ane->id,
            'structure_id' => null,
            'statut' => 'externe',
            'categorie_socioprofessionnelle_id' => null,
            'date_naissance' => null,
            'lieu_naissance' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('demandes.show', $demande));

        $response
            ->assertOk()
            ->assertSeeText('Catégorie socioprofessionnelle')
            ->assertSeeText('Date de naissance')
            ->assertSeeText('Lieu de naissance')
            ->assertDontSeeText('Structure');

        $this->assertGreaterThanOrEqual(3, substr_count($response->getContent(), '>N/A<'));
    }

    public function test_demande_show_displays_missing_required_travail_category_and_hides_optional_dates(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $trv = TypeDocument::where('code', 'TRV')->firstOrFail();
        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $trv->id,
            'categorie_socioprofessionnelle_id' => null,
            'date_fin_service' => null,
            'date_depart_retraite' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('demandes.show', $demande));

        $response
            ->assertOk()
            ->assertSeeText('Catégorie socioprofessionnelle')
            ->assertSeeText('Date de prise de service')
            ->assertDontSeeText('Date de fin de service')
            ->assertDontSeeText('Date de départ à la retraite');

        $this->assertStringContainsString('<dd class="text-ink-900">N/A</dd>', $response->getContent());
    }

    public function test_demande_show_displays_missing_required_administratif_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $adm = TypeDocument::where('code', 'ADM')->firstOrFail();
        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $adm->id,
            'categorie_socioprofessionnelle_id' => null,
            'date_prise_service' => '2024-01-15',
            'date_fin_service' => null,
            'date_depart_retraite' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('demandes.show', $demande));

        $response
            ->assertOk()
            ->assertSeeText('Catégorie socioprofessionnelle')
            ->assertSeeText('Date de prise de service')
            ->assertDontSeeText('Date de fin de service')
            ->assertDontSeeText('Date de départ à la retraite');

        $this->assertStringContainsString('<dd class="text-ink-900">N/A</dd>', $response->getContent());
    }

    public function test_demande_show_renders_document_viewer_for_pdf_and_image_justificatifs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);
        $pdf = FichierJustificatif::create([
            'demande_id' => $demande->id,
            'nom' => 'piece.pdf',
            'chemin' => 'justificatifs/piece.pdf',
            'mime_type' => 'application/pdf',
            'taille' => 1024,
        ]);
        $image = FichierJustificatif::create([
            'demande_id' => $demande->id,
            'nom' => 'photo.png',
            'chemin' => 'justificatifs/photo.png',
            'mime_type' => 'image/png',
            'taille' => 2048,
        ]);

        $response = $this->actingAs($admin)->get(route('demandes.show', $demande));

        $response
            ->assertOk()
            ->assertSee('Visualiser')
            ->assertSee('ouvrirJustificatif', false)
            ->assertSee('\u0022nom\u0022:\u0022piece.pdf\u0022', false)
            ->assertSee('\u0022nom\u0022:\u0022photo.png\u0022', false)
            ->assertSee('Ouvrir dans un nouvel onglet')
            ->assertSee('Dézoomer')
            ->assertSee('Zoomer')
            ->assertSee('Ajuster')
            ->assertSee('createJustificatifViewer', false)
            ->assertSee('x-ref="pdfPages"', false)
            ->assertSee('x-bind:style="`width: ${justificatifZoom * 100}%;`"', false)
            ->assertSee('overflow-y-auto bg-ink-900/70', false)
            ->assertSee('shrink-0 flex-col gap-3 border-b', false)
            ->assertDontSee('class="mt-3 h-96 w-full rounded border border-gray-200"', false)
            ->assertDontSee('<iframe', false);
    }

    public function test_state_change_without_comment_persists_default_comment(): void
    {
        $accueil = User::factory()->create();
        $accueil->assignRole('ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::EN_ATTENTE);

        $response = $this->actingAs($accueil)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::RECEPTIONNEE,
        ]);

        $response
            ->assertRedirect(route('demandes.show', $demande))
            ->assertSessionHas('success');

        $demande->refresh();

        $this->assertSame(EtatDemande::RECEPTIONNEE, $demande->etatDemande->nom);
        $this->assertStringContainsString($accueil->name, $demande->commentaire);
        $this->assertStringContainsString('Sans commentaire', $demande->commentaire);
        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => $accueil->id,
            'commentaire' => 'Sans commentaire',
        ]);
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

    public function test_complement_transition_is_kept_when_recipient_mailbox_rejects_email(): void
    {
        $assignedAgent = User::factory()->create();
        $assignedAgent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $assignedAgent->id]);
        $exception = new TransportException('550 L-RF2 This recipient has been reported recently as out of storage space');

        Mail::shouldReceive('to')
            ->once()
            ->with($demande->email)
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->with(\Mockery::type(DemandeComplementMail::class))
            ->andThrow($exception);
        Log::shouldReceive('warning')
            ->once()
            ->with('Échec d’envoi mail de workflow.', \Mockery::on(
                fn (array $context): bool => $context['action'] === 'demande_complements'
                    && $context['demande_id'] === $demande->id
                    && $context['email'] === $demande->email
                    && str_contains($context['message'], '550 L-RF2')
            ));

        $this->actingAs($assignedAgent)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Pièces manquantes',
        ])->assertRedirect(route('demandes.show', $demande))
            ->assertSessionHas('success', 'État modifié avec succès.')
            ->assertSessionHas('warning', 'L’email de demande de compléments n’a pas pu être envoyé. Contactez le demandeur ou renvoyez le lien plus tard.');

        $demande->refresh();

        $this->assertSame(EtatDemande::COMPLEMENTS, $demande->etatDemande->nom);
        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => $assignedAgent->id,
            'commentaire' => 'Pièces manquantes',
        ]);
    }

    public function test_agent_with_accueil_role_can_request_complements_when_assigned(): void
    {
        Mail::fake();
        ApplicationSetting::setComplementLinkValidityDays(7);

        $agentAccueil = User::factory()->create();
        $agentAccueil->assignRole('AGENT', 'ACCUEIL');
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agentAccueil->id]);

        $this->actingAs($agentAccueil)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Pièces manquantes',
        ])->assertRedirect(route('demandes.show', $demande));

        $demande->refresh();

        $this->assertSame(EtatDemande::COMPLEMENTS, $demande->etatDemande->nom);
        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => $agentAccueil->id,
            'commentaire' => 'Pièces manquantes',
        ]);
        Mail::assertSent(
            DemandeComplementMail::class,
            fn (DemandeComplementMail $mail): bool => str_contains($mail->lien, '/demandes/'.$demande->id.'/edit')
                && $mail->commentaireAgent === 'Pièces manquantes'
                && $mail->validityDays === 7
        );
    }

    public function test_complement_edit_form_action_uses_configured_validity_days(): void
    {
        ApplicationSetting::setComplementLinkValidityDays(6);
        $demande = $this->makeDemande(EtatDemande::COMPLEMENTS);

        $response = $this->get(\URL::signedRoute('demandes.edit', ['demande' => $demande->id]))
            ->assertOk();

        parse_str(parse_url($response->viewData('formAction'), PHP_URL_QUERY), $query);

        $this->assertLessThanOrEqual(5, abs((int) $query['expires'] - now()->addDays(6)->timestamp));
    }

    public function test_assigned_agent_can_resend_complement_link(): void
    {
        Mail::fake();
        ApplicationSetting::setComplementLinkValidityDays(5);

        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::COMPLEMENTS, ['agent_id' => $agent->id]);

        $this->actingAs($agent)->get(route('demandes.show', $demande))
            ->assertOk()
            ->assertSee('Renvoyer le lien de compléments');

        $this->actingAs($agent)->post(route('demandes.renvoyer-complements', $demande))
            ->assertRedirect(route('demandes.show', $demande))
            ->assertSessionHas('success', 'Lien de compléments renvoyé avec succès.');

        Mail::assertSent(
            DemandeComplementMail::class,
            fn (DemandeComplementMail $mail): bool => $mail->demande->is($demande)
                && $mail->validityDays === 5
                && str_contains($mail->lien, '/demandes/'.$demande->id.'/edit')
        );

        $this->assertDatabaseHas('historique_etats', [
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => $agent->id,
            'commentaire' => 'Lien de compléments renvoyé.',
        ]);
    }

    public function test_complement_link_resend_is_only_available_for_complement_state_and_assigned_agent(): void
    {
        Mail::fake();

        $assignedAgent = User::factory()->create();
        $assignedAgent->assignRole('AGENT');
        $otherAgent = User::factory()->create();
        $otherAgent->assignRole('AGENT');

        $validatedDemande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $assignedAgent->id]);
        $complementDemande = $this->makeDemande(EtatDemande::COMPLEMENTS, ['agent_id' => $assignedAgent->id]);

        $this->actingAs($assignedAgent)->get(route('demandes.show', $validatedDemande))
            ->assertOk()
            ->assertDontSee('Renvoyer le lien de compléments');

        $this->actingAs($otherAgent)->post(route('demandes.renvoyer-complements', $complementDemande))
            ->assertForbidden();

        $this->actingAs($assignedAgent)->post(route('demandes.renvoyer-complements', $validatedDemande))
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_replaying_same_state_change_redirects_without_repeating_side_effects(): void
    {
        Mail::fake();

        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $demande = $this->makeDemande(EtatDemande::VALIDEE, ['agent_id' => $agent->id]);

        $this->actingAs($agent)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Pièces manquantes',
        ])->assertRedirect(route('demandes.show', $demande));

        $this->actingAs($agent)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::COMPLEMENTS,
            'commentaire' => 'Pièces manquantes',
        ])->assertRedirect(route('demandes.show', $demande))
            ->assertSessionHas('success', 'État modifié avec succès.');

        $this->assertSame(EtatDemande::COMPLEMENTS, $demande->fresh()->etatDemande->nom);
        $this->assertDatabaseCount('historique_etats', 1);
        Mail::assertSentCount(1);
    }

    public function test_signed_edit_link_only_allows_complement_state(): void
    {
        $complement = $this->makeDemande(EtatDemande::COMPLEMENTS);
        $validee = $this->makeDemande(EtatDemande::VALIDEE);

        $this->get(\URL::signedRoute('demandes.edit', ['demande' => $complement->id]))
            ->assertOk()
            ->assertViewIs('demandes.edit')
            ->assertSee('Compléter une demande')
            ->assertSee('Rechercher une structure')
            ->assertSee('Ajouter des pièces justificatives')
            ->assertDontSee('g-recaptcha', false);

        $this->get(\URL::signedRoute('demandes.edit', ['demande' => $validee->id]))
            ->assertForbidden();
    }

    public function test_update_complemented_demande_returns_it_to_validated_state(): void
    {
        Storage::fake('local');
        $category = CategorieSocioprofessionnelle::firstOrFail();
        $demande = $this->makeDemande(EtatDemande::COMPLEMENTS, [
            'nom' => 'Ancien',
            'categorie_socioprofessionnelle_id' => null,
        ]);
        $structure = Structure::firstOrFail();
        $url = \URL::temporarySignedRoute('demandes.update', now()->addDays(3), ['demande' => $demande->id]);

        $response = $this->put($url, [
            'type_document_id' => $demande->type_document_id,
            'nom' => 'Nouveau',
            'prenom' => $demande->prenom,
            'statut' => 'contractuel',
            'nin' => $demande->nin,
            'structure_id' => $structure->id,
            'telephone' => '+221 77 111 11 11',
            'categorie_socioprofessionnelle_id' => $category->id,
            'fichiers' => [
                UploadedFile::fake()->create('complement.pdf', 64, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('success');

        $demande->refresh();
        $this->assertSame('Nouveau', $demande->nom);
        $this->assertSame($category->id, $demande->categorie_socioprofessionnelle_id);
        $this->assertSame(EtatDemande::VALIDEE, $demande->etatDemande->nom);
        $this->assertDatabaseHas('fichier_justificatifs', ['nom' => 'complement.pdf']);
    }

    public function test_update_complemented_ane_demande_requires_and_persists_birth_fields(): void
    {
        $ane = TypeDocument::where('code', 'ANE')->firstOrFail();
        $category = CategorieSocioprofessionnelle::firstOrFail();
        $demande = $this->makeDemande(EtatDemande::COMPLEMENTS, [
            'type_document_id' => $ane->id,
            'structure_id' => null,
            'statut' => 'externe',
            'categorie_socioprofessionnelle_id' => null,
            'date_naissance' => null,
            'lieu_naissance' => null,
        ]);
        $url = \URL::temporarySignedRoute('demandes.update', now()->addDays(3), ['demande' => $demande->id]);

        $this->get(\URL::signedRoute('demandes.edit', ['demande' => $demande->id]))
            ->assertOk()
            ->assertSee('name="categorie_socioprofessionnelle_id"', false)
            ->assertSee('name="date_naissance"', false)
            ->assertSee('name="lieu_naissance"', false);

        $this->put($url, [
            'type_document_id' => $demande->type_document_id,
            'nom' => $demande->nom,
            'prenom' => $demande->prenom,
            'statut' => 'externe',
            'nin' => $demande->nin,
            'telephone' => '+221 77 111 11 11',
        ])->assertSessionHasErrors([
            'categorie_socioprofessionnelle_id',
            'date_naissance',
            'lieu_naissance',
        ]);

        $this->put($url, [
            'type_document_id' => $demande->type_document_id,
            'nom' => $demande->nom,
            'prenom' => $demande->prenom,
            'statut' => 'externe',
            'nin' => $demande->nin,
            'telephone' => '+221 77 111 11 11',
            'categorie_socioprofessionnelle_id' => $category->id,
            'date_naissance' => '1991-04-20',
            'lieu_naissance' => 'Thiès',
        ])->assertRedirect(route('demandes.create'));

        $demande->refresh();
        $this->assertSame($category->id, $demande->categorie_socioprofessionnelle_id);
        $this->assertSame('1991-04-20', $demande->date_naissance->format('Y-m-d'));
        $this->assertSame('Thiès', $demande->lieu_naissance);
    }

    public function test_update_complemented_demande_requires_valid_signature_and_complement_state(): void
    {
        $complement = $this->makeDemande(EtatDemande::COMPLEMENTS);
        $validee = $this->makeDemande(EtatDemande::VALIDEE);
        $payload = [
            'type_document_id' => $complement->type_document_id,
            'nom' => 'Nouveau',
            'prenom' => $complement->prenom,
            'statut' => 'contractuel',
            'nin' => $complement->nin,
            'structure_id' => $complement->structure_id,
            'telephone' => '+221 77 111 11 11',
        ];

        $this->put(route('demandes.update', $complement), $payload)
            ->assertForbidden();

        $this->put(\URL::temporarySignedRoute('demandes.update', now()->subMinute(), ['demande' => $complement->id]), $payload)
            ->assertForbidden();

        $this->put(\URL::temporarySignedRoute('demandes.update', now()->addDays(3), ['demande' => $validee->id]), array_merge($payload, [
            'type_document_id' => $validee->type_document_id,
            'nin' => $validee->nin,
            'structure_id' => $validee->structure_id,
        ]))->assertForbidden();
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
            ->assertViewHas('typeOptions')
            ->assertSee('Tous les états')
            ->assertSee('Tous les types')
            ->assertSee('En attente')
            ->assertSee('Attestation de travail')
            ->assertSeeInOrder(['Prénom', 'Nom', 'Statut', 'Type', 'État', 'Date', 'Actions'])
            ->assertDontSee('Structure')
            ->assertDontSee("{data: 'structure'", false);
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

    public function test_data_endpoint_filters_rows_by_type_document(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $trv = TypeDocument::where('code', 'TRV')->firstOrFail();
        $afm = TypeDocument::where('code', 'AFM')->firstOrFail();
        $visible = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $trv->id,
            'nom' => 'Visible',
        ]);
        $hidden = $this->makeDemande(EtatDemande::VALIDEE, [
            'type_document_id' => $afm->id,
            'nom' => 'Cache',
        ]);

        $response = $this->actingAs($admin)->getJson(route('demandes.data', [
            'type_document_id' => $trv->id,
        ]));

        $response->assertOk();
        $payload = $response->json('data');

        $this->assertCount(1, $payload);
        $this->assertSame($visible->nom, $payload[0]['nom']);
        $this->assertNotSame($hidden->nom, $payload[0]['nom']);
    }

    public function test_data_endpoint_renders_business_columns_for_table(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $demande = $this->makeDemande(EtatDemande::COMPLEMENTS, [
            'prenom' => 'Fatou',
            'nom' => 'Ndiaye',
            'statut' => 'contractuel',
        ]);

        $response = $this->actingAs($admin)->getJson(route('demandes.data'));

        $response->assertOk();
        $payload = $response->json('data.0');

        $this->assertSame($demande->prenom, $payload['prenom']);
        $this->assertSame($demande->nom, $payload['nom']);
        $this->assertSame('Contractuel', $payload['statut_label']);
        $this->assertStringContainsString('<abbr', $payload['type']);
        $this->assertStringContainsString('title="'.$demande->typeDocument->nom.'"', $payload['type']);
        $this->assertStringContainsString('>'.$demande->typeDocument->code.'</abbr>', $payload['type']);
        $this->assertStringContainsString('Demande de compléments', $payload['etat']);
        $this->assertStringContainsString('bg-amber-100 text-amber-900', $payload['etat']);
    }

    public function test_etat_presentation_uses_complete_badge_palette(): void
    {
        $this->assertSame('En attente', EtatDemande::labelFor(EtatDemande::EN_ATTENTE));
        $this->assertSame('bg-senegal-yellow text-ink-900', EtatDemande::badgeClassFor(EtatDemande::EN_ATTENTE));
        $this->assertSame('bg-amber-100 text-amber-900', EtatDemande::badgeClassFor(EtatDemande::COMPLEMENTS));
        $this->assertSame('bg-indigo-100 text-indigo-800', EtatDemande::badgeClassFor(EtatDemande::EN_SIGNATURE));
        $this->assertSame('bg-gray-300 text-gray-800', EtatDemande::badgeClassFor(EtatDemande::SUSPENDUE));
        $this->assertSame('bg-gray-200 text-ink-700', EtatDemande::badgeClassFor('INCONNU'));
    }

    public function test_tailwind_config_safelists_dynamic_badge_classes(): void
    {
        $tailwindConfig = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringContainsString("'bg-green-700'", $tailwindConfig);
        $this->assertStringContainsString("'text-white'", $tailwindConfig);
        $this->assertStringContainsString("'bg-indigo-100'", $tailwindConfig);
        $this->assertStringContainsString("'text-indigo-800'", $tailwindConfig);
    }

    public function test_data_endpoint_combines_etat_filter_with_agent_scope(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('AGENT');
        $otherAgent = User::factory()->create();
        $otherAgent->assignRole('AGENT');
        $trv = TypeDocument::where('code', 'TRV')->firstOrFail();
        $afm = TypeDocument::where('code', 'AFM')->firstOrFail();
        $visible = $this->makeDemande(EtatDemande::VALIDEE, [
            'agent_id' => $agent->id,
            'type_document_id' => $trv->id,
            'nom' => 'Visible',
        ]);
        $this->makeDemande(EtatDemande::RECEPTIONNEE, [
            'agent_id' => $agent->id,
            'type_document_id' => $trv->id,
            'nom' => 'AutreEtat',
        ]);
        $this->makeDemande(EtatDemande::VALIDEE, [
            'agent_id' => $agent->id,
            'type_document_id' => $afm->id,
            'nom' => 'AutreType',
        ]);
        $hidden = $this->makeDemande(EtatDemande::VALIDEE, [
            'agent_id' => $otherAgent->id,
            'type_document_id' => $trv->id,
            'nom' => 'Cache',
        ]);
        $etatValideeId = EtatDemande::where('nom', EtatDemande::VALIDEE)->value('id');

        $response = $this->actingAs($agent)->getJson(route('demandes.data', [
            'etat_id' => $etatValideeId,
            'type_document_id' => $trv->id,
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

    public function test_pdf_signature_transition_is_kept_when_recipient_mailbox_rejects_email(): void
    {
        Storage::fake('local');
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('A4')->andReturnSelf();
        $pdf->shouldReceive('output')->once()->andReturn('pdf-content');

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(fn (string $view, array $data): bool => $view === 'demandes.pdf.TRV'
                && $data['demande']->verification_code !== null)
            ->andReturn($pdf);

        $exception = new TransportException('550 L-RF2 This recipient has been reported recently as out of storage space');

        Mail::shouldReceive('to')
            ->once()
            ->with('awa.diop@example.test')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->with(\Mockery::type(DemandeSigneeMail::class))
            ->andThrow($exception);
        Log::shouldReceive('warning')
            ->once()
            ->with('Échec d’envoi mail de workflow.', \Mockery::on(
                fn (array $context): bool => $context['action'] === 'demande_signee'
                    && $context['email'] === 'awa.diop@example.test'
                    && str_contains($context['message'], '550 L-RF2')
            ));

        $drh = User::factory()->create();
        $drh->assignRole('DRH');
        $demande = $this->makeDemande(EtatDemande::EN_SIGNATURE);

        $this->actingAs($drh)->post(route('demandes.changerEtat', $demande), [
            'nouvel_etat' => EtatDemande::SIGNEE,
            'commentaire' => 'Signature validée',
        ])->assertRedirect(route('demandes.show', $demande))
            ->assertSessionHas('success', 'État modifié avec succès.')
            ->assertSessionHas('warning', 'La demande a été signée, mais l’email avec le PDF n’a pas pu être envoyé. Contactez le demandeur ou renvoyez le document plus tard.');

        $demande->refresh();

        $this->assertSame(EtatDemande::SIGNEE, $demande->etatDemande->nom);
        $this->assertNotNull($demande->verification_code);
        Storage::disk('local')->assertExists($demande->fichier_pdf);
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
            'categorie_socioprofessionnelle_id' => CategorieSocioprofessionnelle::value('id'),
            'date_prise_service' => '2024-01-15',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'type_document_id' => TypeDocument::where('code', 'TRV')->value('id'),
            'nom' => 'Ndiaye',
            'prenom' => 'Fatou',
            'statut' => 'étatique',
            'nin' => '1234567890123',
            'matricule' => '123456A',
            'structure_id' => Structure::value('id'),
            'email' => 'fatou.ndiaye@example.test',
            'telephone' => '+221 77 123 45 67',
            'categorie_socioprofessionnelle_id' => CategorieSocioprofessionnelle::value('id'),
            'date_prise_service' => '2024-01-15',
            'g-recaptcha-response' => 'valid-token',
        ], $overrides);
    }
}
