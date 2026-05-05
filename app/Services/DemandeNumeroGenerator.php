<?php

namespace App\Services;

use App\Models\Demande;
use App\Models\TypeDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemandeNumeroGenerator
{
    /**
     * @return array{numero_demande: string, numero_annee: int, numero_sequence: int}
     */
    public function genererPour(Demande $demande): array
    {
        $typeDocument = TypeDocument::findOrFail($demande->type_document_id);
        $annee = $this->anneePour($demande);

        return DB::transaction(function () use ($typeDocument, $annee): array {
            DB::table('demande_sequences')->insertOrIgnore([
                'type_document_id' => $typeDocument->id,
                'annee' => $annee,
                'prochain_numero' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('demande_sequences')
                ->where('type_document_id', $typeDocument->id)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->first();

            $numeroSequence = (int) $sequence->prochain_numero;

            DB::table('demande_sequences')
                ->where('id', $sequence->id)
                ->update([
                    'prochain_numero' => $numeroSequence + 1,
                    'updated_at' => now(),
                ]);

            return [
                'numero_demande' => sprintf('%s-%d%05d', $typeDocument->code, $annee, $numeroSequence),
                'numero_annee' => $annee,
                'numero_sequence' => $numeroSequence,
            ];
        });
    }

    private function anneePour(Demande $demande): int
    {
        $createdAt = $demande->created_at ? Carbon::parse($demande->created_at) : now();

        return (int) $createdAt->format('Y');
    }
}
