<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read Collection<int, Demande> $demandes
 * @property-read int|null $demandes_count
 * @property-read Collection<int, HistoriqueEtat> $historiques
 * @property-read int|null $historiques_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EtatDemande newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EtatDemande newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EtatDemande query()
 *
 * @mixin \Eloquent
 */
class EtatDemande extends Model
{
    use HasFactory;

    public const EN_ATTENTE = 'EN ATTENTE';

    public const RECEPTIONNEE = 'RECEPTIONNEE';

    public const VALIDEE = 'VALIDEE';

    public const REFUSEE = 'REFUSEE';

    public const COMPLEMENTS = 'DEMANDE DE COMPLEMENTS';

    public const EN_SIGNATURE = 'EN SIGNATURE';

    public const SIGNEE = 'SIGNEE';

    public const SUSPENDUE = 'SUSPENDUE';

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
            self::SIGNEE => 'Signée',
            self::EN_SIGNATURE => 'En signature',
            self::SUSPENDUE => 'Suspendue',
        ];
    }

    public static function badgeClasses(): array
    {
        return [
            self::EN_ATTENTE => 'bg-senegal-yellow text-ink-900',
            self::RECEPTIONNEE => 'bg-blue-100 text-blue-800',
            self::VALIDEE => 'bg-senegal-green text-white',
            self::REFUSEE => 'bg-senegal-red text-white',
            self::COMPLEMENTS => 'bg-amber-100 text-amber-900',
            self::EN_SIGNATURE => 'bg-indigo-100 text-indigo-800',
            self::SIGNEE => 'bg-green-700 text-white',
            self::SUSPENDUE => 'bg-gray-300 text-gray-800',
        ];
    }

    public static function labelFor(?string $etat): string
    {
        return self::labels()[$etat] ?? ($etat ?: 'N/A');
    }

    public static function badgeClassFor(?string $etat): string
    {
        return self::badgeClasses()[$etat] ?? 'bg-gray-200 text-ink-700';
    }
}
