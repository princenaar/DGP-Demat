<?php

use App\Http\Controllers\DemandeController;
use App\Http\Controllers\JustificatifController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Demandes routes
Route::get('/demandes', [DemandeController::class, 'index'])->name('demandes.index');
Route::post('/demandes', [DemandeController::class, 'store'])->name('demandes.store');
Route::post('/demandes/{demande}/changer-etat', [DemandeController::class, 'changerEtat'])->name('demandes.changerEtat');
Route::get('/demandes/create', [DemandeController::class, 'create'])->name('demandes.create');
Route::get('/demandes/data', [DemandeController::class, 'data'])->name('demandes.data');
Route::get('/demandes/{demande}', [DemandeController::class, 'show'])->name('demandes.show');


Route::get('/justificatifs/{id}', [JustificatifController::class, 'voir'])->name('justificatifs.voir');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:ADMIN'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
});


require __DIR__ . '/auth.php';
