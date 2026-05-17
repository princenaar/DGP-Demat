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
        Schema::create('type_document_default_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['type_document_id', 'user_id'], 'type_document_default_agents_unique');
        });

        DB::table('type_documents')
            ->whereNotNull('default_agent_id')
            ->orderBy('id')
            ->get(['id', 'default_agent_id'])
            ->each(function (object $typeDocument): void {
                DB::table('type_document_default_agents')->insert([
                    'type_document_id' => $typeDocument->id,
                    'user_id' => $typeDocument->default_agent_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_document_default_agents');
    }
};
