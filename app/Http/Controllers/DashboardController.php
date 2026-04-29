<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\EtatDemande;
use App\Models\TypeDocument;
use App\Models\User;
use App\Services\WorkflowEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    public function __construct(private WorkflowEngine $workflowEngine) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $demandesScope = $this->demandesScope($user);
        $demandesATraiter = $this->demandesATraiter($user);

        return view('dashboard', [
            'countsByEtat' => $this->countsByEtat($demandesScope),
            'demandesATraiterCount' => $demandesATraiter->count(),
            'countsByTypeLast30Days' => $this->countsByTypeLast30Days($demandesScope),
            'averageSignatureTime' => $this->averageSignatureTime($demandesScope),
        ]);
    }

    public function data(Request $request)
    {
        $demandes = $this->demandesATraiter($request->user());

        return DataTables::of($demandes)
            ->addColumn('etat', fn (Demande $demande): string => $demande->etatDemande->nom)
            ->addColumn('type', fn (Demande $demande): string => $demande->typeDocument->nom)
            ->addColumn('structure', fn (Demande $demande): string => $demande->structure->nom ?? '-')
            ->addColumn('actions', fn (Demande $demande): string => view('demandes.partials.actions', compact('demande'))->render())
            ->rawColumns(['actions'])
            ->make();
    }

    private function demandesScope(User $user): Builder
    {
        $query = Demande::query();

        if ($user->hasRole('AGENT')) {
            $query->where('agent_id', $user->id);
        } elseif (! $user->hasAnyRole(['ADMIN', 'CHEF_DE_DIVISION', 'DRH'])) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * @param  Builder<Demande>  $query
     * @return array<int, array{nom: string, label: string, total: int}>
     */
    private function countsByEtat(Builder $query): array
    {
        $counts = (clone $query)
            ->selectRaw('etat_demande_id, COUNT(*) as total')
            ->groupBy('etat_demande_id')
            ->pluck('total', 'etat_demande_id');

        return EtatDemande::orderBy('id')
            ->get()
            ->map(fn (EtatDemande $etat): array => [
                'nom' => $etat->nom,
                'label' => EtatDemande::labels()[$etat->nom] ?? $etat->nom,
                'total' => (int) ($counts[$etat->id] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  Builder<Demande>  $query
     * @return array<int, array{nom: string, total: int}>
     */
    private function countsByTypeLast30Days(Builder $query): array
    {
        $counts = (clone $query)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('type_document_id, COUNT(*) as total')
            ->groupBy('type_document_id')
            ->pluck('total', 'type_document_id');

        return TypeDocument::orderBy('nom')
            ->get()
            ->map(fn (TypeDocument $typeDocument): array => [
                'nom' => $typeDocument->nom,
                'total' => (int) ($counts[$typeDocument->id] ?? 0),
            ])
            ->all();
    }

    private function averageSignatureTime(Builder $query): ?string
    {
        $signeId = EtatDemande::where('nom', EtatDemande::SIGNEE)->value('id');

        if (! $signeId) {
            return null;
        }

        $durations = (clone $query)
            ->where('etat_demande_id', $signeId)
            ->with(['historiques' => fn ($query) => $query->where('etat_demande_id', $signeId)])
            ->get()
            ->map(function (Demande $demande) use ($signeId): ?int {
                $signedAt = $demande->historiques
                    ->where('etat_demande_id', $signeId)
                    ->sortByDesc('created_at')
                    ->first()
                    ?->created_at;

                return $signedAt ? $demande->created_at->diffInSeconds($signedAt) : null;
            })
            ->filter();

        if ($durations->isEmpty()) {
            return null;
        }

        $averageSeconds = (int) round($durations->avg());

        if ($averageSeconds < 3600) {
            return max(1, (int) ceil($averageSeconds / 60)).' min';
        }

        if ($averageSeconds < 86400) {
            return round($averageSeconds / 3600, 1).' h';
        }

        return round($averageSeconds / 86400, 1).' j';
    }

    /**
     * @return Collection<int, Demande>
     */
    private function demandesATraiter(User $user): Collection
    {
        $roles = $user->getRoleNames();

        if ($roles->isEmpty()) {
            return new Collection;
        }

        return $this->demandesScope($user)
            ->with('typeDocument', 'etatDemande', 'structure')
            ->orderBy('created_at')
            ->get()
            ->filter(function (Demande $demande) use ($roles): bool {
                return $this->workflowEngine
                    ->transitionsFor($demande)
                    ->contains(fn ($transition): bool => $transition->role_requis && $roles->contains($transition->role_requis));
            })
            ->values();
    }
}
