<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\WorkflowTransitionRequest;
use App\Models\TypeDocument;
use App\Models\WorkflowTransition;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkflowTransitionController extends Controller
{
    public function index(TypeDocument $typeDocument): View
    {
        return view('settings.workflow.index', [
            'typeDocument' => $typeDocument->load('workflowTransitions.etatSource', 'workflowTransitions.etatCible'),
        ]);
    }

    public function update(WorkflowTransitionRequest $request, TypeDocument $typeDocument, WorkflowTransition $workflowTransition): RedirectResponse
    {
        $this->ensureTransitionBelongsToType($typeDocument, $workflowTransition);
        $workflowTransition->update([
            'automatique' => $request->boolean('automatique'),
        ]);

        return redirect()->route('settings.type-documents.workflow.index', $typeDocument)->with('status', 'Transition mise à jour.');
    }

    private function ensureTransitionBelongsToType(TypeDocument $typeDocument, WorkflowTransition $workflowTransition): void
    {
        abort_if($workflowTransition->type_document_id !== $typeDocument->id, 404);
    }
}
