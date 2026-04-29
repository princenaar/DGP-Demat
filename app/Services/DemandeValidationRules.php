<?php

namespace App\Services;

use App\Models\Demande;

class DemandeValidationRules
{
    public function estEligibleAValidationAuto(Demande $demande): bool
    {
        $demande->loadMissing('typeDocument.piecesRequises', 'justificatifs');

        if (! $this->champsRequisSontRenseignes($demande)) {
            return false;
        }

        if (! $this->statutCorrespondAEligibilite($demande)) {
            return false;
        }

        if (! $this->piecesObligatoiresSontPresentes($demande)) {
            return false;
        }

        return $this->datesSontCoherentes($demande);
    }

    private function champsRequisSontRenseignes(Demande $demande): bool
    {
        foreach ($demande->typeDocument?->champs_requis ?? [] as $champ => $obligatoire) {
            if ($obligatoire && blank($demande->{$champ})) {
                return false;
            }
        }

        return true;
    }

    private function statutCorrespondAEligibilite(Demande $demande): bool
    {
        $eligibilite = $demande->typeDocument?->eligibilite;

        return blank($eligibilite) || $demande->statut === $eligibilite;
    }

    private function piecesObligatoiresSontPresentes(Demande $demande): bool
    {
        $piecesObligatoires = $demande->typeDocument?->piecesRequises
            ->where('obligatoire', true) ?? collect();

        if ($piecesObligatoires->isEmpty()) {
            return true;
        }

        return $demande->justificatifs->isNotEmpty();
    }

    private function datesSontCoherentes(Demande $demande): bool
    {
        if (! $demande->date_fin_service || ! $demande->date_prise_service) {
            return true;
        }

        return $demande->date_fin_service->greaterThan($demande->date_prise_service);
    }
}
