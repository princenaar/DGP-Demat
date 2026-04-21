<?php

namespace Tests\Feature;

use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\FichierJustificatif;
use App\Models\Structure;
use App\Models\TypeDocument;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JustificatifTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            EtatDemandeSeeder::class,
            TypeDocumentSeeder::class,
            StructureSeeder::class,
        ]);
    }

    public function test_existing_justificatif_file_is_streamed(): void
    {
        Storage::disk('local')->put('justificatifs/test.txt', 'contenu');
        $demande = $this->makeDemande();
        $fichier = FichierJustificatif::create([
            'demande_id' => $demande->id,
            'nom' => 'test.txt',
            'chemin' => 'justificatifs/test.txt',
            'mime_type' => 'text/plain',
            'taille' => 7,
        ]);

        $this->get(route('justificatifs.voir', $fichier->id))
            ->assertOk();
    }

    public function test_missing_justificatif_file_returns_not_found(): void
    {
        $demande = $this->makeDemande();
        $fichier = FichierJustificatif::create([
            'demande_id' => $demande->id,
            'nom' => 'missing.txt',
            'chemin' => 'justificatifs/missing.txt',
            'mime_type' => 'text/plain',
            'taille' => 0,
        ]);

        $this->get(route('justificatifs.voir', $fichier->id))
            ->assertNotFound();
    }

    private function makeDemande(): Demande
    {
        return Demande::create([
            'type_document_id' => TypeDocument::value('id'),
            'structure_id' => Structure::value('id'),
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa.diop@example.test',
            'telephone' => '771234567',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
        ]);
    }
}
