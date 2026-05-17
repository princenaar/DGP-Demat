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
            'typeDocuments' => TypeDocument::with('defaultAgents', 'piecesRequises', 'workflowTransitions')->orderBy('nom')->get(),
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
        $defaultAgentIds = $this->validatedDefaultAgents($validated['default_agent_ids'] ?? []);
        unset($validated['default_agent_ids']);
        $validated['champs_requis'] = $this->normaliseRequiredFields($validated['champs_requis'] ?? []);

        $typeDocument = TypeDocument::create($validated);
        $typeDocument->defaultAgents()->sync($defaultAgentIds);

        return redirect()->route('settings.type-documents.index')->with('status', 'Type de demande créé.');
    }

    public function show(TypeDocument $typeDocument): RedirectResponse
    {
        return redirect()->route('settings.type-documents.edit', $typeDocument);
    }

    public function edit(TypeDocument $typeDocument): View
    {
        $typeDocument->loadMissing('defaultAgents');

        return view('settings.type-documents.form', [
            'typeDocument' => $typeDocument,
            'agents' => User::role('AGENT')->active()->orderBy('name')->get(),
            'fields' => $this->configurableFields(),
        ]);
    }

    public function update(TypeDocumentRequest $request, TypeDocument $typeDocument): RedirectResponse
    {
        $validated = $request->validated();
        $defaultAgentIds = $this->validatedDefaultAgents($validated['default_agent_ids'] ?? []);
        unset($validated['default_agent_ids']);
        $validated['champs_requis'] = $this->normaliseRequiredFields($validated['champs_requis'] ?? []);

        if ($typeDocument->code === 'ANE') {
            $validated['code'] = 'ANE';
            $validated['eligibilite'] = 'externe';
            $validated['champs_requis'] = [];
        }

        $typeDocument->update($validated);
        $typeDocument->defaultAgents()->sync($defaultAgentIds);

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

    /**
     * @param  array<int, mixed>  $agentIds
     * @return array<int, int>
     */
    private function validatedDefaultAgents(array $agentIds): array
    {
        if ($agentIds === []) {
            return [];
        }

        $validAgentIds = User::role('AGENT')
            ->active()
            ->whereKey($agentIds)
            ->pluck('id')
            ->all();

        if (count($validAgentIds) !== count(array_unique($agentIds))) {
            abort(422, 'Un ou plusieurs agents par défaut sont invalides ou désactivés.');
        }

        return $validAgentIds;
    }
}
