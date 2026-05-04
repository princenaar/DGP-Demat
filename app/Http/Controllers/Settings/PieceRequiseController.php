<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PieceRequiseRequest;
use App\Models\PieceRequise;
use App\Models\TypeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PieceRequiseController extends Controller
{
    public function index(TypeDocument $typeDocument): View
    {
        return view('settings.pieces.index', [
            'typeDocument' => $typeDocument->load('piecesRequises'),
            'pieceRequise' => new PieceRequise(['ordre' => $typeDocument->piecesRequises()->max('ordre') + 1]),
        ]);
    }

    public function store(PieceRequiseRequest $request, TypeDocument $typeDocument): RedirectResponse
    {
        $typeDocument->piecesRequises()->create($this->payload($request));

        return redirect()->route('settings.type-documents.pieces.index', $typeDocument)->with('status', 'Pièce requise ajoutée.');
    }

    public function update(PieceRequiseRequest $request, TypeDocument $typeDocument, PieceRequise $pieceRequise): RedirectResponse
    {
        $this->ensurePieceBelongsToType($typeDocument, $pieceRequise);
        $pieceRequise->update($this->payload($request));

        return redirect()->route('settings.type-documents.pieces.index', $typeDocument)->with('status', 'Pièce requise mise à jour.');
    }

    public function destroy(TypeDocument $typeDocument, PieceRequise $pieceRequise): RedirectResponse
    {
        $this->ensurePieceBelongsToType($typeDocument, $pieceRequise);
        $pieceRequise->delete();

        return redirect()->route('settings.type-documents.pieces.index', $typeDocument)->with('status', 'Pièce requise supprimée.');
    }

    /**
     * @return array{libelle: string, description: string|null, obligatoire: bool, ordre: int}
     */
    private function payload(PieceRequiseRequest $request): array
    {
        $validated = $request->validated();
        $validated['obligatoire'] = $request->boolean('obligatoire');

        return $validated;
    }

    private function ensurePieceBelongsToType(TypeDocument $typeDocument, PieceRequise $pieceRequise): void
    {
        abort_if($pieceRequise->type_document_id !== $typeDocument->id, 404);
    }
}
