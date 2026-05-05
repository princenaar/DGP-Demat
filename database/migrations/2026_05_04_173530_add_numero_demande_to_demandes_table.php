<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->string('numero_demande')->nullable()->after('id');
            $table->unsignedSmallInteger('numero_annee')->nullable()->after('numero_demande');
            $table->unsignedInteger('numero_sequence')->nullable()->after('numero_annee');
        });

        DB::table('demandes')
            ->join('type_documents', 'demandes.type_document_id', '=', 'type_documents.id')
            ->select([
                'demandes.id',
                'demandes.type_document_id',
                'demandes.created_at',
                'type_documents.code',
            ])
            ->orderBy('demandes.type_document_id')
            ->orderBy('demandes.created_at')
            ->orderBy('demandes.id')
            ->get()
            ->groupBy(fn (object $demande): string => $demande->type_document_id.'-'.$this->yearFor($demande))
            ->each(function ($demandes): void {
                $sequence = 1;

                foreach ($demandes as $demande) {
                    $annee = $this->yearFor($demande);

                    DB::table('demandes')
                        ->where('id', $demande->id)
                        ->update([
                            'numero_demande' => sprintf('%s-%d%05d', $demande->code, $annee, $sequence),
                            'numero_annee' => $annee,
                            'numero_sequence' => $sequence,
                        ]);

                    $sequence++;
                }

                $dernier = $demandes->last();
                DB::table('demande_sequences')->insert([
                    'type_document_id' => $dernier->type_document_id,
                    'annee' => $this->yearFor($dernier),
                    'prochain_numero' => $sequence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('demandes', function (Blueprint $table) {
            $table->string('numero_demande')->nullable(false)->change();
            $table->unsignedSmallInteger('numero_annee')->nullable(false)->change();
            $table->unsignedInteger('numero_sequence')->nullable(false)->change();
            $table->unique('numero_demande');
            $table->unique(['type_document_id', 'numero_annee', 'numero_sequence'], 'demandes_type_year_sequence_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropUnique('demandes_numero_demande_unique');
            $table->dropUnique('demandes_type_year_sequence_unique');
            $table->dropColumn(['numero_demande', 'numero_annee', 'numero_sequence']);
        });
    }

    private function yearFor(object $demande): int
    {
        return (int) date('Y', strtotime($demande->created_at ?? 'now'));
    }
};
