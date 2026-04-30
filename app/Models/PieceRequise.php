<?php

namespace App\Models;

use Database\Factories\PieceRequiseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PieceRequise extends Model
{
    /** @use HasFactory<PieceRequiseFactory> */
    use HasFactory;

    protected $table = 'pieces_requises';

    protected $fillable = [
        'type_document_id',
        'libelle',
        'description',
        'obligatoire',
        'ordre',
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
    ];

    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(TypeDocument::class);
    }
}
