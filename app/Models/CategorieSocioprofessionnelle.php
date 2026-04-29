<?php

namespace App\Models;

use Database\Factories\CategorieSocioprofessionnelleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieSocioprofessionnelle extends Model
{
    /** @use HasFactory<CategorieSocioprofessionnelleFactory> */
    use HasFactory;

    protected $table = 'categories_socioprofessionnelles';

    protected $fillable = [
        'libelle',
        'code',
        'ordre',
    ];

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }
}
