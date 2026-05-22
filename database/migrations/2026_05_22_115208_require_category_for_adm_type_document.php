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
        $this->updateAdmRequiredFields(function (array $requiredFields): array {
            $requiredFields['categorie_socioprofessionnelle_id'] = true;

            return $requiredFields;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->updateAdmRequiredFields(function (array $requiredFields): array {
            unset($requiredFields['categorie_socioprofessionnelle_id']);

            return $requiredFields;
        });
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     */
    private function updateAdmRequiredFields(callable $callback): void
    {
        $typeDocument = DB::table('type_documents')
            ->where('code', 'ADM')
            ->first(['id', 'champs_requis']);

        if (! $typeDocument) {
            return;
        }

        $requiredFields = json_decode((string) $typeDocument->champs_requis, true);

        if (! is_array($requiredFields)) {
            $requiredFields = [];
        }

        DB::table('type_documents')
            ->where('id', $typeDocument->id)
            ->update([
                'champs_requis' => json_encode($callback($requiredFields), JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }
};
