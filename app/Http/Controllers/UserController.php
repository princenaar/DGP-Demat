<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all(); // Récupère tous les utilisateurs
        return view('users.index', compact('users')); // Envoie les données à la vue
    }

}
