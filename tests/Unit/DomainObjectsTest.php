<?php

namespace Tests\Unit;

use App\Mail\DemandeComplementMail;
use App\Mail\DemandeSigneeMail;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\FichierJustificatif;
use App\Models\HistoriqueEtat;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Notifications\DemandeNotification;
use App\Services\DemandeMailService;
use Database\Seeders\EtatDemandeSeeder;
use Database\Seeders\StructureSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DomainObjectsTest extends TestCase
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

    public function test_etat_labels_include_all_workflow_states(): void
    {
        $labels = EtatDemande::labels();

        foreach ([
            EtatDemande::EN_ATTENTE,
            EtatDemande::RECEPTIONNEE,
            EtatDemande::VALIDEE,
            EtatDemande::REFUSEE,
            EtatDemande::COMPLEMENTS,
            EtatDemande::EN_SIGNATURE,
            EtatDemande::SIGNEE,
            EtatDemande::SUSPENDUE,
        ] as $etat) {
            $this->assertArrayHasKey($etat, $labels);
        }
    }

    public function test_demande_relationships_resolve_related_models(): void
    {
        $agent = User::factory()->create();
        $demande = $this->makeDemande(['agent_id' => $agent->id]);
        $fichier = FichierJustificatif::create([
            'demande_id' => $demande->id,
            'nom' => 'piece.pdf',
            'chemin' => 'justificatifs/piece.pdf',
            'mime_type' => 'application/pdf',
            'taille' => 100,
        ]);
        $historique = HistoriqueEtat::create([
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => $agent->id,
            'commentaire' => 'Création',
        ]);

        $this->assertTrue($demande->typeDocument->is(TypeDocument::findOrFail($demande->type_document_id)));
        $this->assertTrue($demande->structure->is(Structure::findOrFail($demande->structure_id)));
        $this->assertTrue($demande->etatDemande->is(EtatDemande::findOrFail($demande->etat_demande_id)));
        $this->assertTrue($demande->agent->is($agent));
        $this->assertTrue($demande->justificatifs->first()->is($fichier));
        $this->assertTrue($demande->historiques->first()->is($historique));
        $this->assertTrue($fichier->demande->is($demande));
        $this->assertTrue($historique->demande->is($demande));
        $this->assertTrue($historique->etat->is($demande->etatDemande));
        $this->assertTrue($historique->utilisateur->is($agent));
    }

    public function test_type_document_and_structure_have_demande_relationships(): void
    {
        $demande = $this->makeDemande();

        $this->assertTrue(TypeDocument::findOrFail($demande->type_document_id)->demandes->first()->is($demande));
        $this->assertTrue(Structure::findOrFail($demande->structure_id)->demandes->first()->is($demande));
        $this->assertTrue(EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->first()->demandes->first()->is($demande));
    }

    public function test_notification_builds_mail_with_optional_action(): void
    {
        $notification = new DemandeNotification('Objet', 'Message', 'https://example.test/demande');
        $mail = $notification->toMail(new \stdClass);

        $this->assertSame(['mail'], $notification->via(new \stdClass));
        $this->assertSame('Objet', $mail->subject);
        $this->assertSame([], $notification->toArray(new \stdClass));
        $this->assertStringContainsString('Message', implode("\n", $mail->introLines));
        $this->assertSame('Voir plus...', $mail->actionText);
        $this->assertSame('https://example.test/demande', $mail->actionUrl);
    }

    public function test_demande_mail_service_routes_notification_to_demande_email(): void
    {
        Notification::fake();
        $demande = $this->makeDemande(['email' => 'destinataire@example.test']);

        DemandeMailService::envoyer($demande, 'Objet', 'Message');

        Notification::assertSentOnDemand(DemandeNotification::class);
    }

    public function test_complement_mailable_exposes_markdown_content(): void
    {
        $demande = $this->makeDemande();
        $mail = new DemandeComplementMail($demande, 'https://example.test/complement');

        $this->assertSame('Demande de compléments', $mail->envelope()->subject);
        $this->assertSame('emails.demande.complements', $mail->content()->markdown);
        $this->assertSame($demande, $mail->content()->with['demande']);
        $this->assertSame('https://example.test/complement', $mail->content()->with['url']);
        $this->assertSame([], $mail->attachments());
    }

    public function test_signed_mailable_defines_pdf_attachment(): void
    {
        $demande = $this->makeDemande();
        $mail = new DemandeSigneeMail($demande, 'demandes_signees/test.pdf');

        $this->assertSame('Votre demande a été signée', $mail->envelope()->subject);
        $this->assertSame('emails.demande.signee', $mail->content()->markdown);
        $this->assertCount(1, $mail->attachments());
    }

    private function makeDemande(array $attributes = []): Demande
    {
        return Demande::create(array_merge([
            'type_document_id' => TypeDocument::value('id'),
            'structure_id' => Structure::value('id'),
            'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            'nom' => 'Diop',
            'prenom' => 'Awa',
            'email' => 'awa.diop@example.test',
            'telephone' => '771234567',
            'statut' => 'contractuel',
            'nin' => '1234567890123',
        ], $attributes));
    }
}
