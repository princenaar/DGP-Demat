<?php

namespace Tests\Feature;

use App\Models\CategorieSocioprofessionnelle;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use Database\Seeders\CategorieSocioprofessionnelleSeeder;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
            ->assertSee('Paramètres');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.index'))
            ->assertForbidden();
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
            ->assertSee('Agent par défaut pour l’imputation automatique')
            ->assertSee('Champs requis pour la validation automatique');

        $this->actingAs($this->admin)->post(route('settings.type-documents.store'), [
            'nom' => 'Autorisation test',
            'code' => 'AUT',
            'description' => 'Description',
            'icone' => 'file-check',
            'eligibilite' => 'contractuel',
            'default_agent_id' => $agent->id,
            'champs_requis' => ['date_prise_service' => true],
        ])->assertRedirect(route('settings.type-documents.index'));

        $type = TypeDocument::where('code', 'AUT')->firstOrFail();
        $this->assertSame($agent->id, $type->default_agent_id);
        $this->assertTrue($type->champs_requis['date_prise_service']);

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

        $source = EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->firstOrFail();
        $cible = EtatDemande::where('nom', EtatDemande::RECEPTIONNEE)->firstOrFail();

        $this->get(route('settings.type-documents.workflow.index', $type))
            ->assertOk()
            ->assertSee('Ajouter une transition')
            ->assertSee('État source')
            ->assertSee('État cible')
            ->assertSee('Rôle autorisé')
            ->assertSee('Transition automatique');

        $this->post(route('settings.type-documents.workflow.store', $type), [
            'etat_source_id' => $source->id,
            'etat_cible_id' => $cible->id,
            'role_requis' => 'ACCUEIL',
            'automatique' => '1',
            'ordre' => 1,
        ])->assertRedirect(route('settings.type-documents.workflow.index', $type));

        $this->assertDatabaseHas('workflow_transitions', [
            'type_document_id' => $type->id,
            'role_requis' => 'ACCUEIL',
            'automatique' => true,
        ]);
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
            'libelle' => 'Biologiste',
            'code' => 'BIO',
            'ordre' => 99,
        ])->assertRedirect(route('settings.referentiels.index'));

        $this->assertDatabaseHas('categories_socioprofessionnelles', ['code' => 'BIO']);
        $categorie = CategorieSocioprofessionnelle::where('code', 'BIO')->firstOrFail();

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
}
