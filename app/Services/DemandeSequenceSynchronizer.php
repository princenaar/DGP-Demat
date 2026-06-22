<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemandeSequenceSynchronizer
{
    /**
     * @return Collection<int, array{
     *     type_document_id: int,
     *     code: string|null,
     *     annee: int,
     *     maximum_utilise: int,
     *     prochain_numero: int|null
     * }>
     */
    public function anomalies(): Collection
    {
        return DB::table('demandes as demandes')
            ->join('type_documents as types', 'types.id', '=', 'demandes.type_document_id')
            ->leftJoin('demande_sequences as sequences', function ($join): void {
                $join->on('sequences.type_document_id', '=', 'demandes.type_document_id')
                    ->on('sequences.annee', '=', 'demandes.numero_annee');
            })
            ->select([
                'demandes.type_document_id',
                'types.code',
                'demandes.numero_annee',
                'sequences.id as sequence_id',
                'sequences.prochain_numero',
            ])
            ->selectRaw('MAX(demandes.numero_sequence) as maximum_utilise')
            ->groupBy([
                'demandes.type_document_id',
                'types.code',
                'demandes.numero_annee',
                'sequences.id',
                'sequences.prochain_numero',
            ])
            ->havingRaw('sequences.id IS NULL OR sequences.prochain_numero <= MAX(demandes.numero_sequence)')
            ->orderBy('demandes.numero_annee')
            ->orderBy('demandes.type_document_id')
            ->get()
            ->map(fn (object $anomalie): array => [
                'type_document_id' => (int) $anomalie->type_document_id,
                'code' => $anomalie->code,
                'annee' => (int) $anomalie->numero_annee,
                'maximum_utilise' => (int) $anomalie->maximum_utilise,
                'prochain_numero' => $anomalie->prochain_numero === null
                    ? null
                    : (int) $anomalie->prochain_numero,
            ]);
    }

    public function resynchroniser(): int
    {
        return DB::transaction(function (): int {
            $compteursCorriges = 0;

            foreach ($this->sequencesUtilisees() as $sequenceUtilisee) {
                DB::table('demande_sequences')->insertOrIgnore([
                    'type_document_id' => $sequenceUtilisee->type_document_id,
                    'annee' => $sequenceUtilisee->numero_annee,
                    'prochain_numero' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sequence = DB::table('demande_sequences')
                    ->where('type_document_id', $sequenceUtilisee->type_document_id)
                    ->where('annee', $sequenceUtilisee->numero_annee)
                    ->lockForUpdate()
                    ->first();

                $maximumUtilise = DB::table('demandes')
                    ->where('type_document_id', $sequenceUtilisee->type_document_id)
                    ->where('numero_annee', $sequenceUtilisee->numero_annee)
                    ->max('numero_sequence');

                if ($maximumUtilise === null || (int) $sequence->prochain_numero > (int) $maximumUtilise) {
                    continue;
                }

                DB::table('demande_sequences')
                    ->where('id', $sequence->id)
                    ->update([
                        'prochain_numero' => (int) $maximumUtilise + 1,
                        'updated_at' => now(),
                    ]);

                $compteursCorriges++;
            }

            return $compteursCorriges;
        }, attempts: 3);
    }

    /**
     * @return Collection<int, object{type_document_id: int, numero_annee: int}>
     */
    private function sequencesUtilisees(): Collection
    {
        return DB::table('demandes')
            ->select(['type_document_id', 'numero_annee'])
            ->groupBy(['type_document_id', 'numero_annee'])
            ->orderBy('numero_annee')
            ->orderBy('type_document_id')
            ->get();
    }
}
