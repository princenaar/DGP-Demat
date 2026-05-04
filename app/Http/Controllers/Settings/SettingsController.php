<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CategorieSocioprofessionnelle;
use App\Models\EtatDemande;
use App\Models\PieceRequise;
use App\Models\Structure;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\WorkflowTransition;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('settings.index', [
            'typeDocumentsCount' => TypeDocument::count(),
            'piecesCount' => PieceRequise::count(),
            'workflowTransitionsCount' => WorkflowTransition::count(),
            'structuresCount' => Structure::count(),
            'categoriesCount' => CategorieSocioprofessionnelle::count(),
            'etatsCount' => EtatDemande::count(),
            'usersCount' => User::count(),
            'inactiveUsersCount' => User::where('is_active', false)->count(),
        ]);
    }
}
