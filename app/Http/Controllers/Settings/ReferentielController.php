<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CategorieSocioprofessionnelleRequest;
use App\Http\Requests\Settings\StructureRequest;
use App\Models\CategorieSocioprofessionnelle;
use App\Models\EtatDemande;
use App\Models\Structure;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ReferentielController extends Controller
{
    public function index(): View
    {
        return view('settings.referentiels.index');
    }

    public function structuresData()
    {
        return DataTables::of(Structure::query())
            ->addColumn('actions', fn (Structure $structure): string => view('settings.referentiels.partials.structure-actions', compact('structure'))->render())
            ->rawColumns(['actions'])
            ->make();
    }

    public function categoriesData()
    {
        return DataTables::of(CategorieSocioprofessionnelle::query())
            ->addColumn('actions', fn (CategorieSocioprofessionnelle $categorie): string => view('settings.referentiels.partials.categorie-actions', compact('categorie'))->render())
            ->rawColumns(['actions'])
            ->make();
    }

    public function etatsData()
    {
        return DataTables::of(EtatDemande::query())->make();
    }

    public function createStructure(): View
    {
        return view('settings.referentiels.structure-form', [
            'structure' => new Structure,
        ]);
    }

    public function storeStructure(StructureRequest $request): RedirectResponse
    {
        Structure::create($request->validated());

        return redirect()->route('settings.referentiels.index')->with('status', 'Structure ajoutée.');
    }

    public function editStructure(Structure $structure): View
    {
        return view('settings.referentiels.structure-form', compact('structure'));
    }

    public function updateStructure(StructureRequest $request, Structure $structure): RedirectResponse
    {
        $structure->update($request->validated());

        return redirect()->route('settings.referentiels.index')->with('status', 'Structure mise à jour.');
    }

    public function destroyStructure(Structure $structure): RedirectResponse
    {
        if ($structure->demandes()->exists()) {
            return back()->with('error', 'Cette structure est déjà utilisée.');
        }

        $structure->delete();

        return redirect()->route('settings.referentiels.index')->with('status', 'Structure supprimée.');
    }

    public function createCategorie(): View
    {
        return view('settings.referentiels.categorie-form', [
            'categorie' => new CategorieSocioprofessionnelle(['ordre' => 0]),
        ]);
    }

    public function storeCategorie(CategorieSocioprofessionnelleRequest $request): RedirectResponse
    {
        CategorieSocioprofessionnelle::create($request->validated());

        return redirect()->route('settings.referentiels.index')->with('status', 'Catégorie ajoutée.');
    }

    public function editCategorie(CategorieSocioprofessionnelle $categorie): View
    {
        return view('settings.referentiels.categorie-form', compact('categorie'));
    }

    public function updateCategorie(CategorieSocioprofessionnelleRequest $request, CategorieSocioprofessionnelle $categorie): RedirectResponse
    {
        $categorie->update($request->validated());

        return redirect()->route('settings.referentiels.index')->with('status', 'Catégorie mise à jour.');
    }

    public function destroyCategorie(CategorieSocioprofessionnelle $categorie): RedirectResponse
    {
        if ($categorie->demandes()->exists()) {
            return back()->with('error', 'Cette catégorie est déjà utilisée.');
        }

        $categorie->delete();

        return redirect()->route('settings.referentiels.index')->with('status', 'Catégorie supprimée.');
    }
}
