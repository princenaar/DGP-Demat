<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property-read \App\Models\EtatDemande|null $etat
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistoriqueEtat> $historiques
 * @property-read int|null $historiques_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FichierJustificatif> $justificatifs
 * @property-read int|null $justificatifs_count
 * @property-read \App\Models\Structure|null $structure
 * @property-read \App\Models\TypeDocument|null $typeDocument
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Demande newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Demande newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Demande query()
 * @mixin \Eloquent
 */
class Demande extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'statut',
        'matricule',
        'nin',
        'type_document_id',
        'categorie_socioprofessionnelle',
        'date_prise_service',
        'date_fin_service',
        'date_depart_retraite',
        'agent_id',
        'structure_id',
        'etat_demande_id',
        'commentaire',
        'fichier_pdf',
        'code_qr',
    ];

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
    }

    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }

    public function etatDemande()
    {
        return $this->belongsTo(EtatDemande::class, 'etat_demande_id');
    }

    public function justificatifs()
    {
        return $this->hasMany(FichierJustificatif::class);
    }

    public function historiques()
    {
        return $this->hasMany(HistoriqueEtat::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

}
