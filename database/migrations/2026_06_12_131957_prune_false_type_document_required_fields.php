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
        DB::table('type_documents')
            ->select(['id', 'champs_requis'])
            ->orderBy('id')
            ->chunkById(100, function ($typeDocuments): void {
                foreach ($typeDocuments as $typeDocument) {
                    $fields = json_decode((string) $typeDocument->champs_requis, true);

                    if (! is_array($fields)) {
                        continue;
                    }

                    $requiredFields = array_filter(
                        $fields,
                        fn (mixed $isRequired): bool => (bool) $isRequired
                    );

                    if ($requiredFields === $fields) {
                        continue;
                    }

                    DB::table('type_documents')
                        ->where('id', $typeDocument->id)
                        ->update([
                            'champs_requis' => json_encode($requiredFields, JSON_THROW_ON_ERROR),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
