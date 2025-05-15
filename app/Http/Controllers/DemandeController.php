<?php

namespace App\Http\Controllers;

use App\Models\EtatDemande;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Http\Requests\DemandeStoreRequest;
use App\Models\Demande;
use App\Models\FichierJustificatif;
use App\Services\DemandeMailService;
use Illuminate\Support\Facades\DB;
use Throwable;


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


}
