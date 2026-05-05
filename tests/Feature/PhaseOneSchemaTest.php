<?php

namespace Tests\Feature;

use App\Models\CategorieSocioprofessionnelle;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\PieceRequise;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\WorkflowTransition;
use Database\Seeders\CategorieSocioprofessionnelleSeeder;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\PieceRequiseSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\WorkflowTransitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use SplFileInfo;
use Tests\TestCase;

class PhaseOneSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            EtatDemandeSeeder::class,
            StructureSeeder::class,
            TypeDocumentSeeder::class,
            CategorieSocioprofessionnelleSeeder::class,
            PieceRequiseSeeder::class,
            WorkflowTransitionSeeder::class,
        ]);
    }

    public function test_phase_one_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('categories_socioprofessionnelles'));
        $this->assertTrue(Schema::hasTable('pieces_requises'));
        $this->assertTrue(Schema::hasTable('workflow_transitions'));

        $this->assertTrue(Schema::hasColumns('demandes', [
            'categorie_socioprofessionnelle_id',
            'agent_id',
            'numero_demande',
            'numero_annee',
            'numero_sequence',
            'verification_code',
        ]));

        $this->assertFalse(Schema::hasColumn('demandes', 'categorie_socioprofessionnelle'));

        $this->assertTrue(Schema::hasColumns('type_documents', [
            'eligibilite',
            'default_agent_id',
            'description',
            'icone',
        ]));

        $this->assertTrue(Schema::hasColumns('users', [
            'initial',
            'is_active',
            'deactivated_at',
        ]));
    }

    public function test_migrations_are_consolidated_without_table_alteration_files(): void
    {
        $migrationFiles = collect(glob(database_path('migrations/*.php')) ?: [])
            ->map(fn (string $path): string => (new SplFileInfo($path))->getFilename());

        $this->assertTrue($migrationFiles->contains('2025_05_14_165726_create_demandes_table.php'));
        $this->assertTrue($migrationFiles->contains('0001_01_01_000000_create_users_table.php'));
        $this->assertFalse($migrationFiles->contains(fn (string $file): bool => str_contains($file, '_add_')));

        $expectedTables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'type_documents',
            'etat_demandes',
            'structures',
            'categories_socioprofessionnelles',
            'demandes',
            'fichier_justificatifs',
            'historique_etats',
            'pieces_requises',
            'workflow_transitions',
            'demande_sequences',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                $migrationFiles->contains(fn (string $file): bool => str_contains($file, "create_{$table}_table")),
                "Missing create migration for {$table}."
            );
        }
    }

    public function test_phase_one_seeders_populate_categories_and_type_metadata(): void
    {
        $this->assertSame(320, CategorieSocioprofessionnelle::count());
        $this->assertSame(
            range(1, 320),
            CategorieSocioprofessionnelle::orderBy('ordre')->pluck('ordre')->all()
        );
        $this->assertSame(301, Structure::count());
        $this->assertDatabaseHas('categories_socioprofessionnelles', [
            'libelle' => 'Acteur Communautaire de Santé',
            'code' => 'ACTEUR_COMMUNAUTAIRE_DE_SANTE',
            'ordre' => 1,
        ]);
        $this->assertDatabaseHas('structures', [
            'nom' => 'Cabinet du Ministre',
            'code' => 'CABINET_DU_MINISTRE',
        ]);
        $this->assertSame(
            'Attestation de non engagement avec le ministère de la santé',
            TypeDocument::where('code', 'ANA')->value('nom')
        );
        $this->assertSame('etatique', TypeDocument::where('code', 'ANA')->value('eligibilite'));
        $this->assertNull(TypeDocument::where('code', 'TRV')->value('eligibilite'));
        $this->assertNotNull(TypeDocument::where('code', 'ADM')->value('description'));
        $this->assertSame(0, PieceRequise::count());
    }

    public function test_reference_seeders_are_idempotent(): void
    {
        $this->seed([
            StructureSeeder::class,
            CategorieSocioprofessionnelleSeeder::class,
        ]);

        $this->assertSame(320, CategorieSocioprofessionnelle::count());
        $this->assertSame(301, Structure::count());
    }

    public function test_workflow_transitions_are_seeded_for_each_existing_type(): void
    {
        $this->assertSame(35, WorkflowTransition::count());

        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $source = EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->firstOrFail();
        $cible = EtatDemande::where('nom', EtatDemande::RECEPTIONNEE)->firstOrFail();

        $transition = WorkflowTransition::whereBelongsTo($type)
            ->whereBelongsTo($source, 'etatSource')
            ->whereBelongsTo($cible, 'etatCible')
            ->firstOrFail();

        $this->assertSame('ACCUEIL', $transition->role_requis);
        $this->assertFalse($transition->automatique);
    }

    public function test_new_relationships_resolve(): void
    {
        $agent = User::factory()->create();
        $type = TypeDocument::where('code', 'TRV')->firstOrFail();
        $type->update(['default_agent_id' => $agent->id]);
        $categorie = CategorieSocioprofessionnelle::where('libelle', 'Infirmier breveté')->firstOrFail();

        $demande = Demande::create([
            'type_document_id' => $type->id,
            'structure_id' => Structure::value('id'),
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            'categorie_socioprofessionnelle_id' => $categorie->id,
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa.diop@example.test',
            'telephone' => '771234567',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
        ]);

        $this->assertTrue($demande->categorieSocioprofessionnelle->is($categorie));
        $this->assertTrue($categorie->demandes->first()->is($demande));
        $this->assertTrue($type->defaultAgent->is($agent));
        $this->assertCount(7, $type->workflowTransitions);
    }
}
