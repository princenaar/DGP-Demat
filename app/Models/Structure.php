<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Demande> $demandes
 * @property-read int|null $demandes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure query()
 * @mixin \Eloquent
 */
class Structure extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'code'];

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }
}
