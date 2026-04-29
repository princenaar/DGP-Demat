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
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_document_id')->constrained('type_documents')->cascadeOnDelete();
            $table->foreignId('etat_source_id')->constrained('etat_demandes')->cascadeOnDelete();
            $table->foreignId('etat_cible_id')->constrained('etat_demandes')->cascadeOnDelete();
            $table->string('role_requis')->nullable();
            $table->boolean('automatique')->default(false);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->unique(
                ['type_document_id', 'etat_source_id', 'etat_cible_id'],
                'workflow_transition_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
