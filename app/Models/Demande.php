<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read EtatDemande|null $etat
 * @property-read Collection<int, HistoriqueEtat> $historiques
 * @property-read int|null $historiques_count
 * @property-read Collection<int, FichierJustificatif> $justificatifs
 * @property-read int|null $justificatifs_count
 * @property-read Structure|null $structure
 * @property-read TypeDocument|null $typeDocument
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Demande newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Demande newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Demande query()
 *
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
        'categorie_socioprofessionnelle_id',
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

    protected $casts = [
        'date_prise_service' => 'datetime',
        'date_fin_service' => 'datetime',
        'date_depart_retraite' => 'datetime',
    ];

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
    }

    public function categorieSocioprofessionnelle(): BelongsTo
    {
        return $this->belongsTo(CategorieSocioprofessionnelle::class);
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
