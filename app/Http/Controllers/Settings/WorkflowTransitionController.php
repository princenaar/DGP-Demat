<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\WorkflowTransitionRequest;
use App\Models\EtatDemande;
use App\Models\TypeDocument;
use App\Models\WorkflowTransition;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkflowTransitionController extends Controller
{
    /**
     * @var list<string>
     */
    private array $roles = ['ADMIN', 'ACCUEIL', 'CHEF_DE_DIVISION', 'AGENT', 'DRH'];

    public function index(TypeDocument $typeDocument): View
    {
        return view('settings.workflow.index', [
            'typeDocument' => $typeDocument->load('workflowTransitions.etatSource', 'workflowTransitions.etatCible'),
            'transition' => new WorkflowTransition(['ordre' => $typeDocument->workflowTransitions()->max('ordre') + 1]),
            'etats' => EtatDemande::orderBy('id')->get(),
            'roles' => $this->roles,
        ]);
    }

    public function store(WorkflowTransitionRequest $request, TypeDocument $typeDocument): RedirectResponse
    {
        $typeDocument->workflowTransitions()->create($this->payload($request));

        return redirect()->route('settings.type-documents.workflow.index', $typeDocument)->with('status', 'Transition ajoutée.');
    }

    public function update(WorkflowTransitionRequest $request, TypeDocument $typeDocument, WorkflowTransition $workflowTransition): RedirectResponse
    {
        $this->ensureTransitionBelongsToType($typeDocument, $workflowTransition);
        $workflowTransition->update($this->payload($request));

        return redirect()->route('settings.type-documents.workflow.index', $typeDocument)->with('status', 'Transition mise à jour.');
    }

    public function destroy(TypeDocument $typeDocument, WorkflowTransition $workflowTransition): RedirectResponse
    {
        $this->ensureTransitionBelongsToType($typeDocument, $workflowTransition);
        $workflowTransition->delete();

        return redirect()->route('settings.type-documents.workflow.index', $typeDocument)->with('status', 'Transition supprimée.');
    }

    /**
     * @return array{etat_source_id: int, etat_cible_id: int, role_requis: string, automatique: bool, ordre: int}
     */
    private function payload(WorkflowTransitionRequest $request): array
    {
        $validated = $request->validated();
        $validated['automatique'] = $request->boolean('automatique');

        return $validated;
    }

    private function ensureTransitionBelongsToType(TypeDocument $typeDocument, WorkflowTransition $workflowTransition): void
    {
        abort_if($workflowTransition->type_document_id !== $typeDocument->id, 404);
    }
}
