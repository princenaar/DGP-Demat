<?php

namespace App\Services;

use App\Mail\DemandeComplementMail;
use App\Mail\DemandeSigneeMail;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\HistoriqueEtat;
use App\Models\User;
use App\Models\WorkflowTransition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WorkflowEngine
{
    public function __construct(private DemandeValidationRules $validationRules) {}

    /**
     * @return Collection<int, WorkflowTransition>
     */
    public function transitionsFor(Demande $demande): Collection
    {
        return WorkflowTransition::query()
            ->with('etatSource', 'etatCible')
            ->where('type_document_id', $demande->type_document_id)
            ->where('etat_source_id', $demande->etat_demande_id)
            ->orderBy('ordre')
            ->get();
    }

    public function peut(Demande $demande, EtatDemande $cible, User $user): bool
    {
        $transition = $this->transitionPour($demande, $cible);

        if (! $transition) {
            return false;
        }

        if ($transition->role_requis && ! $user->hasRole($transition->role_requis)) {
            return false;
        }

        if ($cible->nom === EtatDemande::COMPLEMENTS && $demande->agent_id !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{commentaire?: string, agent_id?: int|null}  $payload
     */
    public function transitionner(Demande $demande, EtatDemande $cible, User $user, array $payload = []): Demande
    {
        return DB::transaction(function () use ($demande, $cible, $user, $payload): Demande {
            if (! $this->peut($demande, $cible, $user)) {
                abort(403, 'Action non autorisée.');
            }

            $this->appliquerTransition($demande, $cible, $user, $payload);
            $this->declencherTransitionsAutomatiques($demande, $user);

            return $demande->refresh();
        });
    }

    public function imputer(Demande $demande, User $agent, User $user, ?string $commentaire = null): Demande
    {
        return DB::transaction(function () use ($demande, $agent, $user, $commentaire): Demande {
            $demande->forceFill(['agent_id' => $agent->id])->save();

            $message = $commentaire ?: "Demande imputée à {$agent->name}.";
            $this->ajouterCommentaireAudit($demande, $user, $message);
            $demande->save();
            $this->enregistrerHistorique($demande, $user, $message);

            return $demande->refresh();
        });
    }

    /**
     * @param  array{commentaire?: string, agent_id?: int|null}  $payload
     */
    private function appliquerTransition(Demande $demande, EtatDemande $cible, User $user, array $payload): void
    {
        $commentaire = $payload['commentaire'] ?? null;
        $commentaireAutomatique = null;

        if ($commentaire) {
            $this->ajouterCommentaireAudit($demande, $user, $commentaire);
        }

        $commentaireAutomatique = match ($cible->nom) {
            EtatDemande::RECEPTIONNEE => $this->autoImputerSiConfigure($demande),
            EtatDemande::VALIDEE => $this->imputerDepuisPayload($demande, $payload),
            EtatDemande::COMPLEMENTS => $this->envoyerDemandeComplements($demande),
            EtatDemande::SIGNEE => $this->genererPdfSigneEtNotifier($demande),
            default => null,
        };

        $demande->etat_demande_id = $cible->id;
        $demande->save();

        $this->enregistrerHistorique($demande, $user, $commentaire);

        if ($commentaireAutomatique) {
            $this->enregistrerHistorique($demande, $user, $commentaireAutomatique);
        }
    }

    private function autoImputerSiConfigure(Demande $demande): ?string
    {
        $demande->loadMissing('typeDocument.defaultAgent');
        $agent = $demande->typeDocument?->defaultAgent;

        if (! $agent) {
            return null;
        }

        $demande->agent_id = $agent->id;

        return "Demande imputée automatiquement à {$agent->name}.";
    }

    /**
     * @param  array{agent_id?: int|null}  $payload
     */
    private function imputerDepuisPayload(Demande $demande, array $payload): null
    {
        if (array_key_exists('agent_id', $payload)) {
            $demande->agent_id = $payload['agent_id'];
        }

        return null;
    }

    private function envoyerDemandeComplements(Demande $demande): null
    {
        $lien = URL::temporarySignedRoute(
            'demandes.edit',
            now()->addDays(3),
            ['demande' => $demande->id]
        );

        Mail::to($demande->email)->send(new DemandeComplementMail($demande, $lien));

        return null;
    }

    private function genererPdfSigneEtNotifier(Demande $demande): null
    {
        $demande->code_qr = Str::random(40);
        $pdf = $this->generatePDF($demande);
        $pdfPath = 'demandes_signees/Demande_'.$demande->id.'.pdf';

        $demande->fichier_pdf = $pdfPath;
        Storage::disk('local')->put($pdfPath, $pdf);

        Mail::to($demande->email)->send(new DemandeSigneeMail($demande, $pdfPath));

        return null;
    }

    private function transitionPour(Demande $demande, EtatDemande $cible): ?WorkflowTransition
    {
        return $this->transitionsFor($demande)
            ->first(fn (WorkflowTransition $transition): bool => $transition->etat_cible_id === $cible->id);
    }

    private function declencherTransitionsAutomatiques(Demande $demande, User $user): void
    {
        $transition = $this->transitionsFor($demande)
            ->first(fn (WorkflowTransition $transition): bool => $transition->automatique && $this->gardeAutomatiquePasse($demande, $transition));

        if (! $transition) {
            return;
        }

        $this->appliquerTransition($demande, $transition->etatCible, $user, [
            'commentaire' => 'Transition automatique.',
        ]);

        $this->declencherTransitionsAutomatiques($demande->refresh(), $user);
    }

    private function gardeAutomatiquePasse(Demande $demande, WorkflowTransition $transition): bool
    {
        return $transition->etatSource?->nom === EtatDemande::RECEPTIONNEE
            && $transition->etatCible?->nom === EtatDemande::VALIDEE
            && $this->validationRules->estEligibleAValidationAuto($demande);
    }

    private function ajouterCommentaireAudit(Demande $demande, User $user, string $commentaire): void
    {
        $ancienCommentaire = $demande->commentaire ?? '';
        $nouveauCommentaire = now()->format('d/m/Y H:i').' - '.$user->name.' : '.$commentaire;
        $demande->commentaire = trim($ancienCommentaire."\n".$nouveauCommentaire);
    }

    private function enregistrerHistorique(Demande $demande, ?User $user, ?string $commentaire): void
    {
        HistoriqueEtat::create([
            'demande_id' => $demande->id,
            'etat_demande_id' => $demande->etat_demande_id,
            'user_id' => $user?->id,
            'commentaire' => $commentaire,
        ]);
    }

    private function generatePDF(Demande $demande): string
    {
        if ($demande->code_qr) {
            $qrCode = QrCode::size(100)->generate(route('demandes.verifier', $demande->code_qr));
        } else {
            $qrCode = null;
        }

        $pdf = Pdf::loadView("demandes.pdf.{$demande->typeDocument->code}", compact('demande', 'qrCode'))
            ->setPaper('A4');

        return $pdf->output();
    }
}
