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
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();

            // Données du demandeur
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('telephone')->nullable();
            $table->enum('statut', ['étatique', 'contractuel']);
            $table->string('matricule')->nullable(); // requis si étatique
            $table->string('nin'); // Numéro d'identification nationale

            // Lien avec les autres entités
            $table->foreignId('type_document_id')->constrained('type_documents')->onDelete('restrict');
            $table->foreignId('structure_id')->nullable()->constrained('structures')->onDelete('set null');
            $table->foreignId('etat_demande_id')->constrained('etat_demandes')->onDelete('restrict');
            $table->foreignId('categorie_socioprofessionnelle_id')
                ->nullable()
                ->constrained('categories_socioprofessionnelles')
                ->restrictOnDelete();

            $table->date('date_prise_service')->nullable();
            $table->date('date_fin_service')->nullable();
            $table->date('date_depart_retraite')->nullable();

            // Autres champs
            $table->text('commentaire')->nullable();
            $table->string('fichier_pdf')->nullable(); // path du PDF généré
            $table->string('code_qr')->nullable();     // path ou contenu brut du QR code

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
