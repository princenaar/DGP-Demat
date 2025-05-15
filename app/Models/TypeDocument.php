<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Demande> $demandes
 * @property-read int|null $demandes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TypeDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TypeDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TypeDocument query()
 * @mixin \Eloquent
 */
class TypeDocument extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'code', 'champs_requis'];

    protected $casts = [
        'champs_requis' => 'array',
    ];

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }
}
