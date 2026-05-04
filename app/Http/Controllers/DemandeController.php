<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeStoreRequest;
use App\Http\Requests\DemandeUpdateRequest;
use App\Models\CategorieSocioprofessionnelle;
use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\FichierJustificatif;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Services\DemandeMailService;
use App\Services\WorkflowEngine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class DemandeController extends Controller
{
    public function create()
    {
        $types = TypeDocument::with('piecesRequises')->get();
        $structures = Structure::all();
        $categoriesSocioprofessionnelles = CategorieSocioprofessionnelle::orderBy('ordre')->get();
        $recaptchaSiteKey = config('services.recaptcha.site_key');

        return view('demandes.create', compact('types', 'structures', 'categoriesSocioprofessionnelles', 'recaptchaSiteKey'));
    }

    /**
     * @throws Throwable
     */
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
                'statut' => $request->statut,

                'categorie_socioprofessionnelle_id' => $request->categorie_socioprofessionnelle_id,
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
                'Votre demande a bien été enregistrée sous le numéro '.$demande->id.'. Elle est en cours de traitement.'
            );

            return redirect()->route('demandes.create')->with('success', 'Votre demande a été enregistrée avec succès sous le numéro '.$demande->id.'.');
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
        $agents = User::role('AGENT')->active()->get();
        // Aperçu de la demande finale
        $pdfBase64 = null;
        if ($demande->etatDemande->nom == EtatDemande::VALIDEE || $demande->etatDemande->nom == EtatDemande::EN_SIGNATURE) {
            // Générer le PDF
            $pdf = $this->generatePDF($demande);
            // Convertir le contenu du PDF en Base64
            $pdfBase64 = base64_encode($pdf);
        }

        return view('demandes.show', compact('demande', 'agents', 'pdfBase64'));
    }

    public function data()
    {
        // Vérification du rôle de l'utilisateur
        if (auth()->user()->hasRole('AGENT')) {
            $query = Demande::where('agent_id', auth()->user()->id)
                ->with('typeDocument', 'etatDemande', 'structure');
        } elseif (auth()->user()->hasAnyRole(['ADMIN', 'ACCUEIL', 'CHEF_DE_DIVISION', 'DRH'])) {
            $query = Demande::with('typeDocument', 'etatDemande', 'structure');
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vous pouvez filtrer par rôle ici selon les besoins futurs

        return DataTables::of($query)
            ->addColumn('etat', fn ($demande) => $demande->etatDemande->nom)
            ->addColumn('type', fn ($demande) => $demande->typeDocument->nom)
            ->addColumn('structure', fn ($demande) => $demande->structure->nom ?? '-')
            ->addColumn('actions', function ($demande) {
                return view('demandes.partials.actions', compact('demande'))->render();
            })
            ->rawColumns(['actions'])
            ->make();
    }

    public function changerEtat(Request $request, Demande $demande, WorkflowEngine $workflowEngine)
    {
        $validated = $request->validate([
            'nouvel_etat' => 'required|string',
            'commentaire' => 'required|string',
            'agent_id' => 'nullable|exists:users,id',
        ]);

        $etatFinal = EtatDemande::where('nom', $validated['nouvel_etat'])->first();

        if (! $etatFinal) {
            return back()->withErrors(['Transition invalide.']);
        }

        $transitionExiste = $workflowEngine->transitionsFor($demande)
            ->contains(fn ($transition): bool => $transition->etat_cible_id === $etatFinal->id);

        if (! $transitionExiste) {
            return back()->withErrors(['Transition invalide.']);
        }

        $workflowEngine->transitionner($demande, $etatFinal, $request->user(), $validated);

        return redirect()->route('demandes.show', $demande)->with([
            'success' => 'État modifié avec succès.',
        ]);
    }

    public function imputer(Request $request, Demande $demande, WorkflowEngine $workflowEngine): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => ['required', 'exists:users,id'],
            'commentaire' => ['nullable', 'string'],
        ]);

        $agent = User::role('AGENT')->active()->findOrFail($validated['agent_id']);

        $workflowEngine->imputer(
            $demande,
            $agent,
            $request->user(),
            $validated['commentaire'] ?? null
        );

        return redirect()->route('demandes.show', $demande)->with([
            'success' => 'Demande imputée avec succès.',
        ]);
    }

    /**
     * Affiche le formulaire d'édition de la demande.
     *
     * @return View
     */
    public function edit(Demande $demande)
    {
        if ($demande->etatDemande->nom !== EtatDemande::COMPLEMENTS) {
            abort(403, 'Cette demande ne peut pas être modifiée.');
        }

        $demande->loadMissing('typeDocument.piecesRequises');

        $structures = Structure::all();
        $categoriesSocioprofessionnelles = CategorieSocioprofessionnelle::orderBy('ordre')->get();

        return view('demandes.edit', compact('demande', 'structures', 'categoriesSocioprofessionnelles'));
    }

    /**
     * Met à jour la demande.
     *
     * @param  Request  $request
     * @param  Demande  $demande
     * @return RedirectResponse
     */
    public function update(DemandeUpdateRequest $request)
    {
        // Recuperation de la demande
        $demande = Demande::findOrFail($request->id);
        // Changer l'état de la demande (VALIDÉE à nouveau)
        $demande->etat_demande_id = EtatDemande::where('nom', EtatDemande::VALIDEE)->value('id');
        // Mettre à jour les champs validés
        $demande->update($request->validated());
        // Mettre à jour les fichiers justificatifs
        $this->saveFile($request, $demande);

        // Rediriger vers la page de création de demande avec un message de succès (update not accessible unsigned)
        return redirect()->route('demandes.create')
            ->with('success', 'Votre demande a bien été mise à jour.');
    }

    /**
     * Enregistre les fichiers justificatifs.
     *
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

    public function voirPdf(Demande $demande)
    {
        $pdfPath = $demande->fichier_pdf;

        if (Storage::disk('local')->exists($pdfPath)) {
            $fullPath = Storage::disk('local')->path($pdfPath);

            return response()->file($fullPath);
        }

        return redirect()->back()->withErrors(['Le fichier PDF n\'existe pas.']);
    }

    public function verifier(string $code)
    {
        $demande = Demande::where('code_qr', $code)->first();

        if (! $demande) {
            return view('demandes.verification')->withErrors(['Code QR invalide ou demande non authentique.']);
        }

        return view('demandes.verification', compact('demande'));
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
