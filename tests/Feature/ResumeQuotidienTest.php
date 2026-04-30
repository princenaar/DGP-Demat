<?php

namespace Tests\Feature;

use App\Mail\ResumeQuotidienMail;
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
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResumeQuotidienTest extends TestCase
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

    public function test_resume_quotidien_queues_mail_only_to_users_with_backlog(): void
    {
        Mail::fake();
        $agentAvecBacklog = User::factory()->create(['email' => 'agent@example.test']);
        $agentAvecBacklog->assignRole('AGENT');
        $agentSansBacklog = User::factory()->create(['email' => 'empty@example.test']);
        $agentSansBacklog->assignRole('AGENT');

        $this->makeDemande(EtatDemande::VALIDEE, [
            'agent_id' => $agentAvecBacklog->id,
            'nom' => 'Visible',
        ]);

        $this->artisan('resume:quotidien')
            ->expectsOutput('1 résumé(s) quotidien(s) envoyé(s).')
            ->assertSuccessful();

        Mail::assertQueued(ResumeQuotidienMail::class, function (ResumeQuotidienMail $mail) use ($agentAvecBacklog): bool {
            return $mail->hasTo($agentAvecBacklog->email)
                && $mail->user->is($agentAvecBacklog)
                && $mail->demandes->count() === 1;
        });

        Mail::assertNotQueued(ResumeQuotidienMail::class, fn (ResumeQuotidienMail $mail): bool => $mail->hasTo($agentSansBacklog->email));
    }

    public function test_resume_quotidien_mailable_uses_markdown_template(): void
    {
        $agent = User::factory()->create(['name' => 'Agent Test']);
        $demande = $this->makeDemande(EtatDemande::VALIDEE, [
            'agent_id' => $agent->id,
            'nom' => 'Diop',
        ]);
        $mail = new ResumeQuotidienMail($agent, Demande::whereKey($demande->id)->get());

        $this->assertSame('Résumé quotidien des demandes à traiter', $mail->envelope()->subject);
        $this->assertSame('emails.resume-quotidien', $mail->content()->markdown);
        $mail->assertSeeInHtml('Agent Test');
        $mail->assertSeeInHtml('Diop');
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
