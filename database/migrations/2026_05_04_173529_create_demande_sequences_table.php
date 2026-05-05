<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demande_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_document_id')->constrained('type_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('annee');
            $table->unsignedInteger('prochain_numero')->default(1);
            $table->timestamps();

            $table->unique(['type_document_id', 'annee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_sequences');
    }
};
