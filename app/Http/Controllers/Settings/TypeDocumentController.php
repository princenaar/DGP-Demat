<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TypeDocumentRequest;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TypeDocumentController extends Controller
{
    public function index(): View
    {
        return view('settings.type-documents.index', [
            'typeDocuments' => TypeDocument::with('defaultAgent', 'piecesRequises', 'workflowTransitions')->orderBy('nom')->get(),
        ]);
    }

    public function create(): View
    {
        return view('settings.type-documents.form', [
            'typeDocument' => new TypeDocument,
            'agents' => User::role('AGENT')->active()->orderBy('name')->get(),
            'fields' => $this->configurableFields(),
        ]);
    }

    public function store(TypeDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['default_agent_id'] = $this->validatedDefaultAgent($validated['default_agent_id'] ?? null);
        $validated['champs_requis'] = $this->normaliseRequiredFields($validated['champs_requis'] ?? []);

        TypeDocument::create($validated);

        return redirect()->route('settings.type-documents.index')->with('status', 'Type de demande créé.');
    }

    public function show(TypeDocument $typeDocument): RedirectResponse
    {
        return redirect()->route('settings.type-documents.edit', $typeDocument);
    }

    public function edit(TypeDocument $typeDocument): View
    {
        return view('settings.type-documents.form', [
            'typeDocument' => $typeDocument,
            'agents' => User::role('AGENT')->active()->orderBy('name')->get(),
            'fields' => $this->configurableFields(),
        ]);
    }

    public function update(TypeDocumentRequest $request, TypeDocument $typeDocument): RedirectResponse
    {
        $validated = $request->validated();
        $validated['default_agent_id'] = $this->validatedDefaultAgent($validated['default_agent_id'] ?? null);
        $validated['champs_requis'] = $this->normaliseRequiredFields($validated['champs_requis'] ?? []);

        $typeDocument->update($validated);

        return redirect()->route('settings.type-documents.index')->with('status', 'Type de demande mis à jour.');
    }

    public function destroy(TypeDocument $typeDocument): RedirectResponse
    {
        if ($typeDocument->demandes()->exists()) {
            return back()->with('error', 'Ce type de demande est déjà utilisé.');
        }

        $typeDocument->delete();

        return redirect()->route('settings.type-documents.index')->with('status', 'Type de demande supprimé.');
    }

    /**
     * @return array<string, string>
     */
    private function configurableFields(): array
    {
        return [
            'categorie_socioprofessionnelle_id' => 'Catégorie socioprofessionnelle',
            'date_prise_service' => 'Date de prise de service',
            'date_fin_service' => 'Date de fin de service',
            'date_depart_retraite' => 'Date de départ à la retraite',
        ];
    }

    /**
     * @param  array<string, bool|string|int>  $fields
     * @return array<string, bool>
     */
    private function normaliseRequiredFields(array $fields): array
    {
        return collect($this->configurableFields())
            ->keys()
            ->mapWithKeys(fn (string $field): array => [$field => (bool) ($fields[$field] ?? false)])
            ->all();
    }

    private function validatedDefaultAgent(mixed $agentId): ?int
    {
        if (blank($agentId)) {
            return null;
        }

        return User::role('AGENT')->active()->whereKey($agentId)->value('id')
            ?? abort(422, 'Agent par défaut invalide ou désactivé.');
    }
}
