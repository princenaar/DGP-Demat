<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UserManagementController extends Controller
{
    /**
     * @var list<string>
     */
    private array $roles = ['ADMIN', 'ACCUEIL', 'CHEF_DE_DIVISION', 'AGENT', 'DRH'];

    public function index(): View
    {
        return view('settings.users.index');
    }

    public function data()
    {
        return DataTables::eloquent(User::query()->with('roles'))
            ->addColumn('roles_label', fn (User $user): string => $user->roles->pluck('name')->join(', ') ?: 'Aucun rôle')
            ->addColumn('status_label', fn (User $user): string => $user->is_active ? 'Actif' : 'Désactivé')
            ->addColumn('actions', fn (User $user): string => view('settings.users.partials.actions', compact('user'))->render())
            ->filterColumn('roles_label', function ($query, string $keyword): void {
                $query->whereHas('roles', fn ($rolesQuery) => $rolesQuery->where('name', 'like', "%{$keyword}%"));
            })
            ->rawColumns(['actions'])
            ->make();
    }

    public function create(): View
    {
        return view('settings.users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => $this->roles,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::password(32),
            'is_active' => true,
        ]);
        $user->syncRoles($validated['roles']);

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('settings.users.index')->with('status', 'Utilisateur créé. Un lien de réinitialisation a été envoyé.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('settings.users.form', [
            'user' => $user,
            'roles' => $this->roles,
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        $user->syncRoles($validated['roles']);

        return redirect()->route('settings.users.index')->with('status', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
        ])->save();

        return redirect()->route('settings.users.index')->with('status', 'Utilisateur désactivé.');
    }

    public function reactivate(User $user): RedirectResponse
    {
        $user->forceFill([
            'is_active' => true,
            'deactivated_at' => null,
        ])->save();

        return redirect()->route('settings.users.index')->with('status', 'Utilisateur réactivé.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        if (! $user->is_active) {
            return back()->with('error', 'Réactivez le compte avant de réinitialiser le mot de passe.');
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::ResetLinkSent) {
            return back()->with('error', __($status));
        }

        return redirect()->route('settings.users.index')->with('status', 'Lien de réinitialisation envoyé.');
    }
}
