<?php

namespace App\Services;

use App\Models\Demande;
use App\Models\EtatDemande;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DemandeEtatFilter
{
    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function options(): Collection
    {
        return EtatDemande::orderBy('id')
            ->get()
            ->map(fn (EtatDemande $etat): array => [
                'id' => $etat->id,
                'label' => EtatDemande::labels()[$etat->nom] ?? $etat->nom,
            ]);
    }

    /**
     * @param  Builder<Demande>  $query
     * @return Builder<Demande>
     */
    public function applyToQuery(Builder $query, Request $request): Builder
    {
        $etatId = $this->selectedEtatId($request);

        if ($etatId) {
            $query->where('etat_demande_id', $etatId);
        }

        return $query;
    }

    /**
     * @param  Collection<int, Demande>  $demandes
     * @return Collection<int, Demande>
     */
    public function applyToCollection(Collection $demandes, Request $request): Collection
    {
        $etatId = $this->selectedEtatId($request);

        if (! $etatId) {
            return $demandes;
        }

        return $demandes
            ->filter(fn (Demande $demande): bool => $demande->etat_demande_id === $etatId)
            ->values();
    }

    private function selectedEtatId(Request $request): ?int
    {
        $etatId = $request->query('etat_id');

        if (! is_numeric($etatId)) {
            return null;
        }

        $etatId = (int) $etatId;

        if ($etatId < 1) {
            return null;
        }

        return EtatDemande::whereKey($etatId)->exists() ? $etatId : null;
    }
}
