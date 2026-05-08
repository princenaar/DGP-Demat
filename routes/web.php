<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\JustificatifController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\PieceRequiseController;
use App\Http\Controllers\Settings\ReferentielController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\TypeDocumentController;
use App\Http\Controllers\Settings\UserManagementController;
use App\Http\Controllers\Settings\WorkflowTransitionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request, DashboardController $dashboardController) {
    if ($request->user()) {
        return $dashboardController($request);
    }

    return view('welcome');
});

// Demandes routes
Route::post('/demandes', [DemandeController::class, 'store'])->name('demandes.store');
Route::get('/demandes/{demande}/edit', [DemandeController::class, 'edit'])->name('demandes.edit')->middleware('signed');
Route::put('/demandes/{demande}', [DemandeController::class, 'update'])->name('demandes.update')->middleware('signed');
Route::get('/demandes/create', [DemandeController::class, 'create'])->name('demandes.create');
Route::get('/demandes/verifier/{code}', [DemandeController::class, 'verifier'])->name('demandes.verifier');

Route::middleware('auth')->group(function () {
    Route::get('/demandes', [DemandeController::class, 'index'])->name('demandes.index');
    Route::post('/demandes/{demande}/changer-etat', [DemandeController::class, 'changerEtat'])->name('demandes.changerEtat');
    Route::post('/demandes/{demande}/imputer', [DemandeController::class, 'imputer'])->middleware('role:ADMIN|CHEF_DE_DIVISION')->name('demandes.imputer');
    Route::get('demandes/{demande}/voirPdf', [DemandeController::class, 'voirPdf'])->name('demandes.voirPdf');
    Route::get('/demandes/data', [DemandeController::class, 'data'])->name('demandes.data');
    Route::get('/demandes/{demande}', [DemandeController::class, 'show'])->name('demandes.show');
});

Route::get('/justificatifs/{id}', [JustificatifController::class, 'voir'])->name('justificatifs.voir');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:ADMIN'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    Route::get('/parametres', SettingsController::class)->name('settings.index');
    Route::resource('/parametres/types-demandes', TypeDocumentController::class)
        ->parameters(['types-demandes' => 'typeDocument'])
        ->names('settings.type-documents');
    Route::get('/parametres/types-demandes/{typeDocument}/pieces', [PieceRequiseController::class, 'index'])->name('settings.type-documents.pieces.index');
    Route::post('/parametres/types-demandes/{typeDocument}/pieces', [PieceRequiseController::class, 'store'])->name('settings.type-documents.pieces.store');
    Route::put('/parametres/types-demandes/{typeDocument}/pieces/{pieceRequise}', [PieceRequiseController::class, 'update'])->name('settings.type-documents.pieces.update');
    Route::delete('/parametres/types-demandes/{typeDocument}/pieces/{pieceRequise}', [PieceRequiseController::class, 'destroy'])->name('settings.type-documents.pieces.destroy');
    Route::get('/parametres/types-demandes/{typeDocument}/workflow', [WorkflowTransitionController::class, 'index'])->name('settings.type-documents.workflow.index');
    Route::post('/parametres/types-demandes/{typeDocument}/workflow', [WorkflowTransitionController::class, 'store'])->name('settings.type-documents.workflow.store');
    Route::put('/parametres/types-demandes/{typeDocument}/workflow/{workflowTransition}', [WorkflowTransitionController::class, 'update'])->name('settings.type-documents.workflow.update');
    Route::delete('/parametres/types-demandes/{typeDocument}/workflow/{workflowTransition}', [WorkflowTransitionController::class, 'destroy'])->name('settings.type-documents.workflow.destroy');
    Route::get('/parametres/referentiels', [ReferentielController::class, 'index'])->name('settings.referentiels.index');
    Route::get('/parametres/referentiels/structures/data', [ReferentielController::class, 'structuresData'])->name('settings.structures.data');
    Route::get('/parametres/referentiels/categories/data', [ReferentielController::class, 'categoriesData'])->name('settings.categories.data');
    Route::get('/parametres/referentiels/etats/data', [ReferentielController::class, 'etatsData'])->name('settings.etats.data');
    Route::get('/parametres/referentiels/structures/create', [ReferentielController::class, 'createStructure'])->name('settings.structures.create');
    Route::post('/parametres/referentiels/structures', [ReferentielController::class, 'storeStructure'])->name('settings.structures.store');
    Route::get('/parametres/referentiels/structures/{structure}/edit', [ReferentielController::class, 'editStructure'])->name('settings.structures.edit');
    Route::put('/parametres/referentiels/structures/{structure}', [ReferentielController::class, 'updateStructure'])->name('settings.structures.update');
    Route::delete('/parametres/referentiels/structures/{structure}', [ReferentielController::class, 'destroyStructure'])->name('settings.structures.destroy');
    Route::get('/parametres/referentiels/categories/create', [ReferentielController::class, 'createCategorie'])->name('settings.categories.create');
    Route::post('/parametres/referentiels/categories', [ReferentielController::class, 'storeCategorie'])->name('settings.categories.store');
    Route::get('/parametres/referentiels/categories/{categorie}/edit', [ReferentielController::class, 'editCategorie'])->name('settings.categories.edit');
    Route::put('/parametres/referentiels/categories/{categorie}', [ReferentielController::class, 'updateCategorie'])->name('settings.categories.update');
    Route::delete('/parametres/referentiels/categories/{categorie}', [ReferentielController::class, 'destroyCategorie'])->name('settings.categories.destroy');
    Route::get('/parametres/utilisateurs', [UserManagementController::class, 'index'])->name('settings.users.index');
    Route::get('/parametres/utilisateurs/data', [UserManagementController::class, 'data'])->name('settings.users.data');
    Route::get('/parametres/utilisateurs/create', [UserManagementController::class, 'create'])->name('settings.users.create');
    Route::post('/parametres/utilisateurs', [UserManagementController::class, 'store'])->name('settings.users.store');
    Route::get('/parametres/utilisateurs/{user}/edit', [UserManagementController::class, 'edit'])->name('settings.users.edit');
    Route::put('/parametres/utilisateurs/{user}', [UserManagementController::class, 'update'])->name('settings.users.update');
    Route::delete('/parametres/utilisateurs/{user}', [UserManagementController::class, 'destroy'])->name('settings.users.destroy');
    Route::post('/parametres/utilisateurs/{user}/reactiver', [UserManagementController::class, 'reactivate'])->name('settings.users.reactivate');
    Route::post('/parametres/utilisateurs/{user}/reinitialiser-mot-de-passe', [UserManagementController::class, 'resetPassword'])->name('settings.users.reset-password');
});

require __DIR__.'/auth.php';
