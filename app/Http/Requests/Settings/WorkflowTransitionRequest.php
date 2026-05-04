<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowTransitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('ADMIN') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'etat_source_id' => ['required', 'exists:etat_demandes,id'],
            'etat_cible_id' => ['required', 'exists:etat_demandes,id', 'different:etat_source_id'],
            'role_requis' => ['required', Rule::in(['ADMIN', 'ACCUEIL', 'CHEF_DE_DIVISION', 'AGENT', 'DRH'])],
            'automatique' => ['nullable', 'boolean'],
            'ordre' => ['required', 'integer', 'min:0'],
        ];
    }
}
