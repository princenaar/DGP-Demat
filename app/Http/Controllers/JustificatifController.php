<?php

namespace App\Http\Controllers;

use App\Models\FichierJustificatif;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JustificatifController extends Controller
{
    public function voir(int $id): BinaryFileResponse
    {
        $fichier = FichierJustificatif::findOrFail($id);

        $chemin = Storage::disk('local')->path($fichier->chemin);

        if (! file_exists($chemin)) {
            abort(404);
        }

        return response()->file($chemin);
    }
}
