<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, Demande> $demandes
 * @property-read int|null $demandes_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TypeDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TypeDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TypeDocument query()
 *
 * @mixin \Eloquent
 */
class TypeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'champs_requis',
        'eligibilite',
        'default_agent_id',
        'description',
        'icone',
    ];

    protected $casts = [
        'champs_requis' => 'array',
    ];

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }

    public function defaultAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_agent_id');
    }

    public function piecesRequises(): HasMany
    {
        return $this->hasMany(PieceRequise::class)->orderBy('ordre');
    }

    public function workflowTransitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class)->orderBy('ordre');
    }
}
