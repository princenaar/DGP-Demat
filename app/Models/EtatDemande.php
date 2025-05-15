<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Demande> $demandes
 * @property-read int|null $demandes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistoriqueEtat> $historiques
 * @property-read int|null $historiques_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EtatDemande newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EtatDemande newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EtatDemande query()
 * @mixin \Eloquent
 */
class EtatDemande extends Model
{
    use HasFactory;

    public const EN_ATTENTE = 'en_attente';
    public const RECEPTIONNEE = 'receptionnee';
    public const VALIDEE = 'validee';
    public const REFUSEE = 'refusee';
    public const COMPLEMENTS = 'demande_complements';
    public const EN_SIGNATURE = 'en_signature';
    public const SUSPENDUE = 'suspendue';

    protected $fillable = ['nom'];

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }

    public function historiques()
    {
        return $this->hasMany(HistoriqueEtat::class);
    }

    public static function labels(): array
    {
        return [
            self::EN_ATTENTE => 'En attente',
            self::RECEPTIONNEE => 'Réceptionnée',
            self::VALIDEE => 'Validée',
            self::REFUSEE => 'Refusée',
            self::COMPLEMENTS => 'Demande de compléments',
            self::EN_SIGNATURE => 'En signature',
            self::SUSPENDUE => 'Suspendue',
        ];
    }
}
