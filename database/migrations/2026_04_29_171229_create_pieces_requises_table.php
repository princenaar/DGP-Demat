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
        Schema::create('pieces_requises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_document_id')->constrained('type_documents')->cascadeOnDelete();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->boolean('obligatoire')->default(true);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pieces_requises');
    }
};
