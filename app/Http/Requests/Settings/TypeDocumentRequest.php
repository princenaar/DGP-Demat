<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TypeDocumentRequest extends FormRequest
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
        $typeDocument = $this->route('typeDocument');
        $eligibilities = $typeDocument?->code === 'ANE'
            ? ['etatique', 'étatique', 'contractuel', 'externe']
            : ['etatique', 'étatique', 'contractuel'];

        return [
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('type_documents', 'code')->ignore($typeDocument?->id)],
            'description' => ['nullable', 'string'],
            'icone' => ['nullable', 'string', 'max:255'],
            'eligibilite' => ['nullable', Rule::in($eligibilities)],
            'default_agent_ids' => ['nullable', 'array'],
            'default_agent_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'champs_requis' => ['nullable', 'array'],
            'champs_requis.*' => ['boolean'],
        ];
    }
}
