<?php

use App\Http\Controllers\DemandeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Demandes routes
Route::post('/demandes', [DemandeController::class, 'store'])->name('demandes.store');
Route::get('/demandes/create', [DemandeController::class, 'create'])->name('demandes.create');


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
