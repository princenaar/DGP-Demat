<?php

namespace App\Http\Controllers;

use App\Models\FichierJustificatif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JustificatifController extends Controller
{
    public function voir($id)
    {
        $fichier = FichierJustificatif::findOrFail($id);

        $chemin = Storage::disk('local')->path($fichier->chemin);

        //dd($chemin);

        if (!file_exists($chemin)) {
            abort(404);
        }

        return response()->file($chemin);
    }

}
