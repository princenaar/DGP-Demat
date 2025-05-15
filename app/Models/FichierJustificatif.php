<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property-read \App\Models\Demande|null $demande
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FichierJustificatif newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FichierJustificatif newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FichierJustificatif query()
 * @mixin \Eloquent
 */
class FichierJustificatif extends Model
{
    use HasFactory;

    protected $fillable = ['demande_id', 'nom', 'chemin', 'mime_type', 'taille'];

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }
}
