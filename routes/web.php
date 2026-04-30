<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\JustificatifController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Demandes routes
Route::post('/demandes', [DemandeController::class, 'store'])->name('demandes.store');
Route::get('/demandes/{demande}/edit', [DemandeController::class, 'edit'])->name('demandes.edit')->middleware('signed');
Route::put('/demandes/update', [DemandeController::class, 'update'])->name('demandes.update');
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
});

require __DIR__.'/auth.php';
