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
            ->where('eligibilite', 'etatique')
            ->update([
                'eligibilite' => 'étatique',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: do not reintroduce the legacy unaccented value.
    }
};
