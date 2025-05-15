<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemandeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_document_id' => ['required', 'exists:type_documents,id'],
            'nom' => ['required', 'string'],
            'prenom' => ['required', 'string'],
            'nin' => ['required', 'string'],
            'matricule' => ['nullable', 'string'],
            'structure_id' => ['required', 'exists:structures,id'],

            'email' => ['required', 'email'],
            'telephone' => ['nullable', 'string', 'max:15'],

            // Champs conditionnels potentiels
            'categorie_socioprofessionnelle' => ['nullable', 'string'],
            'date_prise_service' => ['nullable', 'date'],
            'date_fin_service' => ['nullable', 'date'],
            'date_depart_retraite' => ['nullable', 'date'],

            // Fichiers
            'fichiers.*' => ['nullable', 'file', 'max:5120'], // max 5Mo par fichier
        ];
    }
}
