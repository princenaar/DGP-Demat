<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fichier_justificatifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_id')->constrained()->onDelete('cascade');

            $table->string('nom');           // nom original ou label
            $table->string('chemin');        // path du fichier sur le disque
            $table->string('mime_type');     // type MIME (pdf, png, jpeg, etc.)
            $table->unsignedBigInteger('taille'); // en octets

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fichier_justificatifs');
    }
};
