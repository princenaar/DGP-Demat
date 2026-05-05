<?php

namespace App\Models;

use App\Services\DemandeNumeroGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'numero_demande',
        'numero_annee',
        'numero_sequence',
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
        'verification_code',
    ];

    protected $casts = [
        'date_prise_service' => 'datetime',
        'date_fin_service' => 'datetime',
        'date_depart_retraite' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Demande $demande): void {
            if ($demande->numero_demande) {
                return;
            }

            $demande->forceFill(app(DemandeNumeroGenerator::class)->genererPour($demande));
        });
    }

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

    public function getNumeroAfficheAttribute(): string
    {
        return $this->numero_demande ?? (string) $this->id;
    }

    public function getInitialesAgentAttribute(): string
    {
        $agent = $this->agent;

        if (! $agent) {
            return 'NA';
        }

        if ($agent->initial) {
            return $agent->initial;
        }

        $initiales = collect(preg_split('/\s+/', trim(Str::ascii($agent->name))) ?: [])
            ->filter()
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->join('');

        return $initiales !== '' ? $initiales : 'NA';
    }

    public function getReferenceDocumentAttribute(): string
    {
        return 'N° <b>'.e($this->numero_affiche).'</b> MSHP/DRH/DGP/'.e($this->initiales_agent);
    }
}
