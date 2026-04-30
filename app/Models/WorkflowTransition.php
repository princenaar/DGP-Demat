<?php

namespace App\Models;

use Database\Factories\WorkflowTransitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    /** @use HasFactory<WorkflowTransitionFactory> */
    use HasFactory;

    protected $fillable = [
        'type_document_id',
        'etat_source_id',
        'etat_cible_id',
        'role_requis',
        'automatique',
        'ordre',
    ];

    protected $casts = [
        'automatique' => 'boolean',
    ];

    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(TypeDocument::class);
    }

    public function etatSource(): BelongsTo
    {
        return $this->belongsTo(EtatDemande::class, 'etat_source_id');
    }

    public function etatCible(): BelongsTo
    {
        return $this->belongsTo(EtatDemande::class, 'etat_cible_id');
    }
}
