<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE demandes MODIFY statut ENUM('étatique', 'contractuel', 'externe') NOT NULL");
        }

        $anaId = DB::table('type_documents')->where('code', 'ANA')->value('id');

        if ($anaId === null) {
            return;
        }

        DB::table('type_documents')
            ->where('id', $anaId)
            ->update([
                'code' => 'ANE',
                'eligibilite' => 'externe',
                'champs_requis' => json_encode([], JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        DB::table('demandes')
            ->where('type_document_id', $anaId)
            ->update([
                'statut' => 'externe',
                'matricule' => null,
                'structure_id' => null,
                'categorie_socioprofessionnelle_id' => null,
                'date_depart_retraite' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $aneId = DB::table('type_documents')->where('code', 'ANE')->value('id');

        if ($aneId !== null) {
            DB::table('demandes')
                ->where('type_document_id', $aneId)
                ->where('statut', 'externe')
                ->update([
                    'statut' => 'étatique',
                    'updated_at' => now(),
                ]);

            DB::table('type_documents')
                ->where('id', $aneId)
                ->update([
                    'code' => 'ANA',
                    'eligibilite' => 'etatique',
                    'champs_requis' => json_encode([
                        'date_depart_retraite' => true,
                    ], JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE demandes MODIFY statut ENUM('étatique', 'contractuel') NOT NULL");
        }
    }
};
