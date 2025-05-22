<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeUpdateRequest;
use App\Mail\DemandeComplementMail;
use App\Models\EtatDemande;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Http\Requests\DemandeStoreRequest;
use App\Models\Demande;
use App\Models\FichierJustificatif;
use App\Models\User;
use App\Services\DemandeMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;
use Yajra\DataTables\Facades\DataTables;


class DemandeController extends Controller
{
    public function create()
    {
        $types = TypeDocument::all();
        $structures = Structure::all();

        return view('demandes.create', compact('types', 'structures'));
    }


    public function store(DemandeStoreRequest $request)
    {
        DB::beginTransaction();

        try {
            // Création de la demande
            $demande = Demande::create([
                'type_document_id' => $request->type_document_id,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'nin' => $request->nin,
                'matricule' => $request->matricule,
                'structure_id' => $request->structure_id,
                'email' => $request->email,
                'telephone' => $request->telephone,

                'categorie_socioprofessionnelle' => $request->categorie_socioprofessionnelle,
                'date_prise_service' => $request->date_prise_service,
                'date_fin_service' => $request->date_fin_service,
                'date_depart_retraite' => $request->date_depart_retraite,

                'etat_demande_id' => EtatDemande::where('nom', EtatDemande::EN_ATTENTE)->value('id'),
            ]);

            // Enregistrement des justificatifs
            $this->saveFile($request, $demande);

            DB::commit();

            // Envoi de la notification par mail
            DemandeMailService::envoyer(
                $demande,
                'Confirmation de votre demande',
                'Votre demande a bien été enregistrée sous le numéro ' . $demande->id . '. Elle est en cours de traitement.'
            );

            return redirect()->route('demandes.create')->with('success', 'Votre demande a été enregistrée avec succès sous le numéro ' . $demande->id . '.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['error' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
    }

    public function index()
    {
        return view('demandes.index');
    }

    public function show(Demande $demande)
    {
        $agents = User::role('AGENT')->get();
        return view('demandes.show', compact('demande', 'agents'));
    }


    public function data()
    {
        // Vérification du rôle de l'utilisateur
        if (auth()->user()->hasRole('AGENT')) {
            $query = Demande::where('agent_id', auth()->user()->id)
                ->with('typeDocument', 'etatDemande', 'structure');
        } elseif (auth()->user()->hasAnyRole(['ADMIN', 'CHEF_DE_DIVISION', 'DRH'])) {
            $query = Demande::with('typeDocument', 'etatDemande', 'structure');
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vous pouvez filtrer par rôle ici selon les besoins futurs

        return DataTables::of($query)
            ->addColumn('etat', fn($demande) => $demande->etatDemande->nom)
            ->addColumn('type', fn($demande) => $demande->typeDocument->nom)
            ->addColumn('structure', fn($demande) => $demande->structure->nom ?? '-')
            ->addColumn('actions', function ($demande) {
                return view('demandes.partials.actions', compact('demande'))->render();
            })
            ->rawColumns(['actions'])
            ->make();
    }

    public function changerEtat(Request $request, Demande $demande)
    {
        $request->validate([
            'nouvel_etat' => 'required|string',
            'commentaire' => 'required|string',
            'agent_id' => 'nullable|exists:users,id',
        ]);

        Log::info("Validation des données OK pour la demande ID: {$demande->id}");

        $ancienCommentaire = $demande->commentaire ?? '';
        $nouveauCommentaire = now()->format('d/m/Y H:i') . ' - ' . auth()->user()->name . ' : ' . $request->commentaire;
        $demande->commentaire = trim($ancienCommentaire . "\n" . $nouveauCommentaire);

        $etatInitial = $demande->etatDemande;
        $etatFinal = $request->nouvel_etat;

        Log::info("Changement d'état de [{$etatInitial->nom}] à [{$etatFinal}] pour la demande ID: {$demande->id}");

        // Vérification et logique métier si besoin ici
        $transitionsValides = [
            EtatDemande::EN_ATTENTE => [EtatDemande::RECEPTIONNEE],
            EtatDemande::RECEPTIONNEE => [EtatDemande::VALIDEE, EtatDemande::REFUSEE],
            EtatDemande::VALIDEE => [EtatDemande::COMPLEMENTS, EtatDemande::EN_SIGNATURE],
            EtatDemande::EN_SIGNATURE => [EtatDemande::SIGNEE, EtatDemande::SUSPENDUE],
        ];

        Log::info("Transitions à faire : " . json_encode($transitionsValides[$etatInitial->nom]));
        Log::info("Nouvel état : {$etatFinal}");

        if (!isset($transitionsValides[$etatInitial->nom]) || !in_array($etatFinal, $transitionsValides[$etatInitial->nom])) {
            return back()->withErrors(['Transition invalide.']);
        }

        Log::info("Transition valide de {$etatInitial->nom} à {$etatFinal} pour la demande ID: {$demande->id}");

        // Gestion des rôles et actions spécifiques
        switch ($etatFinal) {
            case EtatDemande::VALIDEE:
                $demande->agent_id = $request->agent_id;
                Log::info("Assignation de l'agent ID: {$request->agent_id} à la demande ID: {$demande->id}");
                break;

            case EtatDemande::COMPLEMENTS:
                // Mail avec lien temporaire (à implémenter dans une méthode dédiée)
                if (auth()->id() !== $demande->agent_id) {
                    abort(403, 'Action non autorisée.');
                }

                // Générer un lien temporaire vers demande.edit (valable 3 jours)
                $lien = URL::temporarySignedRoute(
                    'demandes.edit',
                    now()->addDays(3),
                    ['demande' => $demande->id]
                );

                // Envoyer un mail au demandeur
                Mail::to($demande->email)->send(new DemandeComplementMail($demande, $lien));
                break;

            case EtatDemande::EN_SIGNATURE:

                break;

            case EtatDemande::SIGNEE:
                // Génération du PDF final + envoi par mail
                break;

            case EtatDemande::SUSPENDUE:
                // Notification au demandeur
                break;
        }


        $etatFinalModel = EtatDemande::where('nom', $etatFinal)->first();
        $demande->etat_demande_id = $etatFinalModel->id;
        Log::info("Etat de la demande ID: {$demande->id} mis à jour à {$etatFinalModel->nom}");
        $demande->save();
        Log::info("Demande ID: {$demande->id} mise à jour avec succès.");

        return redirect()->route('demandes.show', $demande)->with('success', 'État modifié avec succès.');
    }

    /**
     * Affiche le formulaire d'édition de la demande.
     *
     * @param Demande $demande
     * @return \Illuminate\View\View
     */
    public function edit(Demande $demande)
    {
        if ($demande->etatDemande->nom !== EtatDemande::COMPLEMENTS) {
            abort(403, 'Cette demande ne peut pas être modifiée.');
        }

        $structures = Structure::all();

        return view('demandes.edit', compact('demande', 'structures'));
    }

    /**
     * Met à jour la demande.
     *
     * @param Request $request
     * @param Demande $demande
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(DemandeUpdateRequest $request)
    {
        //Recuperation de la demande
        $demande = Demande::findOrFail($request->id);
        //Changer l'état de la demande (VALIDÉE à nouveau)
        $demande->etat_demande_id = EtatDemande::where('nom', EtatDemande::VALIDEE)->value('id');
        // Mettre à jour les champs validés
        $demande->update($request->validated());
        // Mettre à jour les fichiers justificatifs
        $this->saveFile($request, $demande);

        //Rediriger vers la page de création de demande avec un message de succès (update not accessible unsigned)
        return redirect()->route('demandes.create', $demande)
            ->with('success', 'Votre demande a bien été mise à jour.');
    }

    /**
     * Enregistre les fichiers justificatifs.
     *
     * @param Request $request
     * @param Demande $demande
     * @return void
     */
    private function saveFile(Request $request, Demande $demande)
    {
        // Gestion des fichiers justificatifs
        if ($request->hasFile('fichiers')) {
            foreach ($request->file('fichiers') as $fichier) {
                $path = $fichier->store('justificatifs', 'local');

                FichierJustificatif::create([
                    'demande_id' => $demande->id,
                    'nom' => $fichier->getClientOriginalName(),
                    'chemin' => $path,
                    'mime_type' => $fichier->getMimeType(),
                    'taille' => $fichier->getSize(),
                ]);
            }
        }
    }

}
