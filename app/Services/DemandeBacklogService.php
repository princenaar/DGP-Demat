<?php

namespace App\Services;

use App\Models\Demande;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DemandeBacklogService
{
    public function __construct(private WorkflowEngine $workflowEngine) {}

    public function demandesScope(User $user): Builder
    {
        $query = Demande::query();

        if ($user->hasRole('AGENT')) {
            $query->where(function (Builder $query) use ($user): void {
                $query->where('agent_id', $user->id)
                    ->orWhere(function (Builder $query) use ($user): void {
                        $query->whereNull('agent_id')
                            ->whereHas('typeDocument.defaultAgents', fn (Builder $query): Builder => $query
                                ->whereKey($user->id)
                                ->active());
                    });
            });
        } elseif (! $user->hasAnyRole(['ADMIN', 'ACCUEIL', 'CHEF_DE_DIVISION', 'DRH'])) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * @return Collection<int, Demande>
     */
    public function demandesATraiter(User $user): Collection
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
