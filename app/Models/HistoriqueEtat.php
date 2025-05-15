<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property-read \App\Models\Demande|null $demande
 * @property-read \App\Models\EtatDemande|null $etat
 * @property-read \App\Models\User|null $utilisateur
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoriqueEtat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoriqueEtat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistoriqueEtat query()
 * @mixin \Eloquent
 */
class HistoriqueEtat extends Model
{
    use HasFactory;

    protected $fillable = [
        'demande_id',
        'etat_demande_id',
        'user_id',
        'commentaire',
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }

    public function etat()
    {
        return $this->belongsTo(EtatDemande::class, 'etat_demande_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
