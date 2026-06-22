<?php

namespace Tests\Feature;

use App\Enums\DemandeStatut;
use App\Models\ApplicationSetting;
use App\Models\CategorieSocioprofessionnelle;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Services\DemandeSequenceSynchronizer;
use Database\Seeders\CategorieSocioprofessionnelleSeeder;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolesAndPermissionsSeeder::class,
            EtatDemandeSeeder::class,
            StructureSeeder::class,
            TypeDocumentSeeder::class,
            CategorieSocioprofessionnelleSeeder::class,
            WorkflowTransitionSeeder::class,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('ADMIN');
    }

    public function test_admin_can_access_settings_and_non_admin_cannot(): void
    {
        $this->actingAs($this->admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Paramètres')
            ->assertSee('Liens de compléments')
            ->assertSee('Validité des liens (jours)');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_complement_link_validity_days(): void
    {
        $this->actingAs($this->admin)->put(route('settings.application.update'), [
            'complement_link_validity_days' => 12,
        ])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('status', 'Paramètres applicatifs mis à jour.');

        $this->assertSame(12, ApplicationSetting::complementLinkValidityDays());
    }

    public function test_complement_link_validity_days_must_be_between_one_and_fifteen(): void
    {
        foreach ([0, 16, '', 'abc'] as $value) {
            $this->actingAs($this->admin)->from(route('settings.index'))->put(route('settings.application.update'), [
                'complement_link_validity_days' => $value,
            ])
                ->assertRedirect(route('settings.index'))
                ->assertSessionHasErrors('complement_link_validity_days');
        }
    }

    public function test_non_admin_cannot_update_application_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.application.update'), [
            'complement_link_validity_days' => 10,
        ])->assertForbidden();

        $this->assertSame(3, ApplicationSetting::complementLinkValidityDays());
    }

    public function test_settings_display_sequence_status_and_conditional_resynchronization_button(): void
    {
        $this->actingAs($this->admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Toutes les séquences sont synchronisées')
            ->assertDontSee('Resynchroniser les séquences');

        $type = TypeDocument::where('code', 'ADM')->firstOrFail();
        $this->insertImportedDemande($type, 2026, 7);

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('1 compteur(s) absent(s) ou en retard')
            ->assertSee('Resynchroniser les séquences');
    }

    public function test_sequence_synchronizer_detects_missing_and_lagging_counters(): void
    {
        $adm = TypeDocument::where('code', 'ADM')->firstOrFail();
        $trv = TypeDocument::where('code', 'TRV')->firstOrFail();

        $this->insertImportedDemande($adm, 2025, 4);
        $this->insertImportedDemande($trv, 2026, 3);
        $this->insertSequence($trv, 2026, 3);

        $anomalies = app(DemandeSequenceSynchronizer::class)->anomalies();

        $this->assertCount(2, $anomalies);
        $this->assertSame([
            [$adm->id, 2025, 4, null],
            [$trv->id, 2026, 3, 3],
        ], $anomalies
            ->map(fn (array $anomalie): array => [
                $anomalie['type_document_id'],
                $anomalie['annee'],
                $anomalie['maximum_utilise'],
                $anomalie['prochain_numero'],
            ])
            ->values()
            ->all());
    }

    public function test_admin_can_resynchronize_all_sequences_without_decreasing_advanced_counters(): void
    {
        $adm = TypeDocument::where('code', 'ADM')->firstOrFail();
        $trv = TypeDocument::where('code', 'TRV')->firstOrFail();
        $afm = TypeDocument::where('code', 'AFM')->firstOrFail();

        $this->insertImportedDemande($adm, 2025, 4);
        $this->insertImportedDemande($trv, 2026, 7);
        $this->insertSequence($trv, 2026, 7);
        $this->insertImportedDemande($afm, 2026, 3);
        $this->insertSequence($afm, 2026, 10);

        $this->actingAs($this->admin)
            ->post(route('settings.sequences.resynchronize'))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('status', '2 compteurs de demandes ont été resynchronisés.');

        $this->assertDatabaseHas('demande_sequences', [
            'type_document_id' => $adm->id,
            'annee' => 2025,
            'prochain_numero' => 5,
        ]);
        $this->assertDatabaseHas('demande_sequences', [
            'type_document_id' => $trv->id,
            'annee' => 2026,
            'prochain_numero' => 8,
        ]);
        $this->assertDatabaseHas('demande_sequences', [
            'type_document_id' => $afm->id,
            'annee' => 2026,
            'prochain_numero' => 10,
        ]);

        $this->post(route('settings.sequences.resynchronize'))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('status', 'Les séquences étaient déjà synchronisées.');

        $this->assertSame(10, (int) DB::table('demande_sequences')
            ->where('type_document_id', $afm->id)
            ->where('annee', 2026)
            ->value('prochain_numero'));
    }

    public function test_new_demande_uses_the_next_available_number_after_resynchronization(): void
    {
        $type = TypeDocument::where('code', 'ADM')->firstOrFail();
        $annee = (int) now()->format('Y');

        $this->insertImportedDemande($type, $annee, 7);

        $this->actingAs($this->admin)
            ->post(route('settings.sequences.resynchronize'))
            ->assertSessionHasNoErrors();

        $demande = Demande::create([
            'type_document_id' => $type->id,
            'structure_id' => Structure::value('id'),
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            'categorie_socioprofessionnelle_id' => CategorieSocioprofessionnelle::value('id'),
            'nom' => 'Ndiaye',
            'prenom' => 'Fatou',
            'email' => 'fatou.ndiaye@example.test',
            'telephone' => '771234568',
            'statut' => 'contractuel',
            'nin' => '1234567890124',
            'date_prise_service' => '2024-01-15',
        ]);

        $this->assertSame("ADM-{$annee}00008", $demande->numero_demande);
        $this->assertSame(8, $demande->numero_sequence);
    }

    public function test_non_admin_cannot_resynchronize_sequences(): void
    {
        $type = TypeDocument::where('code', 'ADM')->firstOrFail();
        $this->insertImportedDemande($type, 2026, 7);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.sequences.resynchronize'))
            ->assertForbidden();

        $this->assertDatabaseMissing('demande_sequences', [
            'type_document_id' => $type->id,
            'annee' => 2026,
        ]);
    }

    public function test_admin_can_manage_type_document_piece_and_workflow(): void
    {
        $agent = User::factory()->create(['name' => 'Agent actif']);
        $agent->assignRole('AGENT');

        $this->actingAs($this->admin)->get(route('settings.type-documents.create'))
            ->assertOk()
            ->assertSee('Paramétrage métier')
            ->assertSee('Nom affiché au demandeur')
            ->assertSee('Code métier')
            ->assertSee('Agents par défaut pour la file partagée')
            ->assertSee('Champs requis pour la validation automatique');

        $this->actingAs($this->admin)->post(route('settings.type-documents.store'), [
            'nom' => 'Autorisation test',
            'code' => 'AUT',
            'description' => 'Description',
            'icone' => 'file-check',
            'eligibilite' => 'contractuel',
            'default_agent_ids' => [$agent->id],
            'champs_requis' => ['date_prise_service' => true],
        ])->assertRedirect(route('settings.type-documents.index'));

        $type = TypeDocument::where('code', 'AUT')->firstOrFail();
        $this->assertTrue($type->defaultAgents->first()->is($agent));
        $this->assertSame(['date_prise_service' => true], $type->champs_requis);

        $this->get(route('settings.type-documents.pieces.index', $type))
            ->assertOk()
            ->assertSee('Type de demande')
            ->assertSee('Autorisation test')
            ->assertSee('Libellé de la pièce')
            ->assertSee('Description ou consigne')
            ->assertSee('Pièce obligatoire');

        $this->post(route('settings.type-documents.pieces.store', $type), [
            'libelle' => 'Carte nationale',
            'description' => 'Copie lisible',
            'obligatoire' => '1',
            'ordre' => 1,
        ])->assertRedirect(route('settings.type-documents.pieces.index', $type));

        $this->assertDatabaseHas('pieces_requises', [
            'type_document_id' => $type->id,
            'libelle' => 'Carte nationale',
            'obligatoire' => true,
        ]);

        $this->assertSame(7, $type->workflowTransitions()->count());
        $transition = $type->workflowTransitions()
            ->whereBelongsTo(EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->firstOrFail(), 'etatSource')
            ->whereBelongsTo(EtatDemande::where('nom', EtatDemande::RECEPTIONNEE)->firstOrFail(), 'etatCible')
            ->firstOrFail();

        $this->get(route('settings.type-documents.workflow.index', $type))
            ->assertOk()
            ->assertSee('Transitions du circuit')
            ->assertDontSee('Ajouter une transition')
            ->assertSee('État source')
            ->assertSee('État cible')
            ->assertSee('Rôle autorisé')
            ->assertSee('EN ATTENTE')
            ->assertSee('RECEPTIONNEE')
            ->assertSee('ACCUEIL')
            ->assertSee('Automatique')
            ->assertDontSee('Supprimer');

        $this->put(route('settings.type-documents.workflow.update', [$type, $transition]), [
            'automatique' => '1',
        ])->assertRedirect(route('settings.type-documents.workflow.index', $type));

        $this->assertDatabaseHas('workflow_transitions', [
            'type_document_id' => $type->id,
            'role_requis' => 'ACCUEIL',
            'automatique' => true,
        ]);
    }

    public function test_etatique_type_document_eligibility_is_stored_with_canonical_value(): void
    {
        $this->actingAs($this->admin)->post(route('settings.type-documents.store'), [
            'nom' => 'Document étatique',
            'code' => 'DET',
            'description' => 'Description',
            'icone' => 'file-check',
            'eligibilite' => DemandeStatut::Etatique->value,
            'champs_requis' => ['date_prise_service' => true],
        ])->assertRedirect(route('settings.type-documents.index'));

        $type = TypeDocument::where('code', 'DET')->firstOrFail();

        $this->assertSame(DemandeStatut::Etatique, $type->eligibilite);
        $this->assertDatabaseHas('type_documents', [
            'id' => $type->id,
            'eligibilite' => DemandeStatut::Etatique->value,
        ]);

        $this->actingAs($this->admin)->put(route('settings.type-documents.update', $type), [
            'nom' => $type->nom,
            'code' => $type->code,
            'description' => $type->description,
            'icone' => $type->icone,
            'eligibilite' => 'etatique',
            'champs_requis' => ['date_prise_service' => true],
        ])->assertRedirect(route('settings.type-documents.index'));

        $type->refresh();

        $this->assertSame(DemandeStatut::Etatique, $type->eligibilite);
        $this->assertDatabaseHas('type_documents', [
            'id' => $type->id,
            'eligibilite' => DemandeStatut::Etatique->value,
        ]);
    }

    public function test_unchecked_type_document_fields_are_not_persisted_as_false_flags(): void
    {
        $this->actingAs($this->admin)->post(route('settings.type-documents.store'), [
            'nom' => 'Document sans champs',
            'code' => 'DSC',
            'description' => 'Description',
            'icone' => 'file-check',
            'eligibilite' => null,
            'champs_requis' => [
                'date_prise_service' => '1',
                'date_fin_service' => '0',
            ],
        ])->assertRedirect(route('settings.type-documents.index'));

        $type = TypeDocument::where('code', 'DSC')->firstOrFail();

        $this->assertSame(['date_prise_service' => true], $type->champs_requis);

        $this->actingAs($this->admin)->put(route('settings.type-documents.update', $type), [
            'nom' => $type->nom,
            'code' => $type->code,
            'description' => $type->description,
            'icone' => $type->icone,
            'eligibilite' => null,
        ])->assertRedirect(route('settings.type-documents.index'));

        $this->assertSame([], $type->fresh()->champs_requis);
    }

    public function test_workflow_update_only_changes_automatic_flag(): void
    {
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $transition = $type->workflowTransitions()->firstOrFail();
        $original = $transition->only(['etat_source_id', 'etat_cible_id', 'role_requis', 'ordre']);

        $this->actingAs($this->admin)->put(route('settings.type-documents.workflow.update', [$type, $transition]), [
            'etat_source_id' => EtatDemande::where('nom', EtatDemande::SIGNEE)->value('id'),
            'etat_cible_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            'role_requis' => 'DRH',
            'ordre' => 99,
            'automatique' => '1',
        ])->assertRedirect(route('settings.type-documents.workflow.index', $type));

        $transition->refresh();

        $this->assertSame($original['etat_source_id'], $transition->etat_source_id);
        $this->assertSame($original['etat_cible_id'], $transition->etat_cible_id);
        $this->assertSame($original['role_requis'], $transition->role_requis);
        $this->assertSame($original['ordre'], $transition->ordre);
        $this->assertTrue($transition->automatique);
    }

    public function test_workflow_create_and_delete_routes_are_not_registered(): void
    {
        $routes = collect(app('router')->getRoutes())->map(fn ($route): ?string => $route->getName());

        $this->assertFalse($routes->contains('settings.type-documents.workflow.store'));
        $this->assertFalse($routes->contains('settings.type-documents.workflow.destroy'));
    }

    public function test_workflow_template_seed_does_not_reset_automatic_flags(): void
    {
        $transition = TypeDocument::where('code', 'TRV')->firstOrFail()
            ->workflowTransitions()
            ->firstOrFail();
        $transition->forceFill(['automatique' => true])->save();

        $this->seed(WorkflowTransitionSeeder::class);

        $this->assertTrue($transition->fresh()->automatique);
    }

    public function test_ane_keeps_reserved_code_and_external_eligibility_when_updated(): void
    {
        $ane = TypeDocument::where('code', 'ANE')->firstOrFail();

        $this->actingAs($this->admin)->get(route('settings.type-documents.edit', $ane))
            ->assertOk()
            ->assertSee('value="ANE"', false)
            ->assertSee('value="Externe"', false)
            ->assertDontSee('<option value="externe"', false);

        $this->actingAs($this->admin)->put(route('settings.type-documents.update', $ane), [
            'nom' => $ane->nom,
            'code' => 'AUTRE',
            'description' => $ane->description,
            'icone' => $ane->icone,
            'eligibilite' => 'contractuel',
            'champs_requis' => [
                'date_naissance' => true,
                'lieu_naissance' => true,
            ],
        ])->assertRedirect(route('settings.type-documents.index'));

        $ane->refresh();

        $this->assertSame('ANE', $ane->code);
        $this->assertSame(DemandeStatut::Externe, $ane->eligibilite);
        $this->assertSame([
            'date_naissance' => true,
            'lieu_naissance' => true,
        ], $ane->champs_requis);
    }

    public function test_admin_can_manage_referentials_and_etats_are_read_only(): void
    {
        $this->actingAs($this->admin)->get(route('settings.referentiels.index'))
            ->assertOk()
            ->assertSee('structures-table')
            ->assertSee('categories-table')
            ->assertSee('etats-table')
            ->assertSee('États des demandes')
            ->assertSee('modification se fait dans le code');

        $this->get(route('settings.structures.create'))
            ->assertOk()
            ->assertSee('Ajouter une structure')
            ->assertSee('Référentiel organisationnel')
            ->assertSee('Nom de la structure')
            ->assertSee('Code interne');

        $this->post(route('settings.structures.store'), [
            'nom' => 'Direction test',
            'code' => 'DIRTEST',
        ])->assertRedirect(route('settings.referentiels.index'));

        $this->assertDatabaseHas('structures', ['code' => 'DIRTEST']);
        $structure = Structure::where('code', 'DIRTEST')->firstOrFail();

        $this->get(route('settings.structures.edit', $structure))
            ->assertOk()
            ->assertSee('Modifier la structure')
            ->assertSee('Nom de la structure');

        $this->get(route('settings.categories.create'))
            ->assertOk()
            ->assertSee('Ajouter une catégorie')
            ->assertSee('Référentiel métier')
            ->assertSee('Libellé affiché')
            ->assertSee('Ordre d’affichage');

        $this->post(route('settings.categories.store'), [
            'libelle' => 'Catégorie test préproduction',
            'code' => 'CAT_TEST_PREPROD',
            'ordre' => 99,
        ])->assertRedirect(route('settings.referentiels.index'));

        $this->assertDatabaseHas('categories_socioprofessionnelles', ['code' => 'CAT_TEST_PREPROD']);
        $categorie = CategorieSocioprofessionnelle::where('code', 'CAT_TEST_PREPROD')->firstOrFail();

        $this->get(route('settings.categories.edit', $categorie))
            ->assertOk()
            ->assertSee('Modifier la catégorie')
            ->assertSee('Libellé affiché');

        $this->assertFalse(collect(app('router')->getRoutes())->contains(fn ($route): bool => str_starts_with($route->uri(), 'parametres/etats')));
    }

    public function test_admin_can_read_paginated_settings_data_endpoints(): void
    {
        User::factory()->count(3)->create();

        $this->actingAs($this->admin)->getJson(route('settings.users.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonCount(2, 'data');

        $this->getJson(route('settings.structures.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);

        $this->getJson(route('settings.categories.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);

        $this->getJson(route('settings.etats.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    }

    public function test_non_admin_cannot_read_settings_data_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('settings.users.data'))->assertForbidden();
        $this->actingAs($user)->getJson(route('settings.structures.data'))->assertForbidden();
        $this->actingAs($user)->getJson(route('settings.categories.data'))->assertForbidden();
        $this->actingAs($user)->getJson(route('settings.etats.data'))->assertForbidden();
    }

    public function test_admin_can_create_user_and_send_password_definition_link(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Agent créé',
            'email' => 'agent.cree@example.test',
            'initial' => 'ac',
            'roles' => ['AGENT'],
        ])
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('status', 'Utilisateur créé. Un lien de réinitialisation a été envoyé.');

        $user = User::where('email', 'agent.cree@example.test')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertSame('AC', $user->initial);
        $this->assertTrue($user->hasRole('AGENT'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_is_not_created_when_password_definition_link_cannot_be_sent(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'agent.echec@example.test'])
            ->andReturn('passwords.user');

        $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Agent échec',
            'email' => 'agent.echec@example.test',
            'initial' => 'ae',
            'roles' => ['AGENT'],
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Utilisateur non créé : le lien de définition du mot de passe n’a pas pu être envoyé.');

        $this->assertDatabaseMissing('users', [
            'email' => 'agent.echec@example.test',
        ]);
    }

    public function test_used_referentials_cannot_be_deleted(): void
    {
        $structure = Structure::firstOrFail();
        $categorie = CategorieSocioprofessionnelle::firstOrFail();

        Demande::create([
            'type_document_id' => TypeDocument::value('id'),
            'structure_id' => $structure->id,
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            'categorie_socioprofessionnelle_id' => $categorie->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa.diop@example.test',
            'telephone' => '771234567',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
        ]);

        $this->actingAs($this->admin)->delete(route('settings.structures.destroy', $structure))
            ->assertSessionHas('error');
        $this->delete(route('settings.categories.destroy', $categorie))
            ->assertSessionHas('error');

        $this->assertModelExists($structure);
        $this->assertModelExists($categorie);
    }

    public function test_admin_can_manage_users_activation_and_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'agent@example.test']);
        $user->assignRole('AGENT');

        $this->actingAs($this->admin)->get(route('settings.users.index'))
            ->assertOk()
            ->assertSee('users-table');

        $this->get(route('settings.users.create'))
            ->assertOk()
            ->assertSee('Ajouter un utilisateur')
            ->assertSee('Compte applicatif')
            ->assertSee('Nom complet')
            ->assertSee('Initiales')
            ->assertSee('Adresse email professionnelle')
            ->assertSee('Rôles applicatifs')
            ->assertSee('Réception des nouvelles demandes');

        $this->get(route('settings.users.edit', $user))
            ->assertOk()
            ->assertSee('Modifier l’utilisateur')
            ->assertSee('Adresse email professionnelle');

        $this->actingAs($this->admin)->put(route('settings.users.update', $user), [
            'name' => 'Agent modifié',
            'email' => 'agent.modifie@example.test',
            'initial' => 'am',
            'roles' => ['ACCUEIL'],
        ])->assertRedirect(route('settings.users.index'));

        $this->assertTrue($user->fresh()->hasRole('ACCUEIL'));
        $this->assertSame('AM', $user->fresh()->initial);

        $this->post(route('settings.users.reset-password', $user->fresh()))
            ->assertRedirect(route('settings.users.index'));
        Notification::assertSentTo($user->fresh(), ResetPassword::class);

        $this->delete(route('settings.users.destroy', $user->fresh()))
            ->assertRedirect(route('settings.users.index'));

        $this->assertFalse($user->fresh()->is_active);

        $this->post(route('settings.users.reactivate', $user->fresh()))
            ->assertRedirect(route('settings.users.index'));

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_deactivated_user_cannot_login_and_is_hidden_from_agent_choices(): void
    {
        $agent = User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'password',
            'is_active' => false,
            'deactivated_at' => now(),
        ]);
        $agent->assignRole('AGENT');

        $this->post(route('login'), [
            'email' => 'inactive@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($this->admin)->get(route('settings.type-documents.create'))
            ->assertOk()
            ->assertDontSee('inactive@example.test')
            ->assertDontSee($agent->name);
    }

    private function insertImportedDemande(TypeDocument $typeDocument, int $annee, int $sequence): void
    {
        DB::table('demandes')->insert([
            'numero_demande' => sprintf('%s-%d%05d', $typeDocument->code, $annee, $sequence),
            'numero_annee' => $annee,
            'numero_sequence' => $sequence,
            'type_document_id' => $typeDocument->id,
            'structure_id' => Structure::value('id'),
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            'categorie_socioprofessionnelle_id' => CategorieSocioprofessionnelle::value('id'),
            'nom' => 'Import',
            'prenom' => $typeDocument->code,
            'email' => strtolower($typeDocument->code).".{$annee}.{$sequence}@example.test",
            'telephone' => null,
            'statut' => 'contractuel',
            'matricule' => null,
            'nin' => sprintf('%013d', ($typeDocument->id * 100000) + $annee + $sequence),
            'created_at' => "{$annee}-01-01 00:00:00",
            'updated_at' => "{$annee}-01-01 00:00:00",
        ]);
    }

    private function insertSequence(
        TypeDocument $typeDocument,
        int $annee,
        int $prochainNumero
    ): void {
        DB::table('demande_sequences')->insert([
            'type_document_id' => $typeDocument->id,
            'annee' => $annee,
            'prochain_numero' => $prochainNumero,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
