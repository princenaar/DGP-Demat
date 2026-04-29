<?php

namespace App\Http\Requests;

use App\Models\Demande;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class DemandeUpdateRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string'],
            'prenom' => ['required', 'string'],
            'nin' => ['required', 'string'],
            'statut' => ['required', 'string', 'in:étatique,contractuel'],
            'matricule' => ['required_if:statut,étatique', 'string'],
            'structure_id' => ['required', 'exists:structures,id'],

            'telephone' => ['nullable', 'string', 'max:15'],

            // Champs conditionnels potentiels
            'categorie_socioprofessionnelle_id' => ['nullable', 'exists:categories_socioprofessionnelles,id'],
            'date_prise_service' => ['nullable', 'date'],
            'date_fin_service' => ['nullable', 'date'],
            'date_depart_retraite' => ['nullable', 'date'],

            // Fichiers
            'fichiers.*' => ['nullable', 'file', 'max:5120'], // max 5Mo par fichier
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateTypeDocumentFields($validator);
                $this->validateEligibility($validator);
                $this->validateRequiredFiles($validator);
            },
        ];
    }

    private function validateTypeDocumentFields(Validator $validator): void
    {
        $demande = $this->demande();
        $typeDocument = $demande?->typeDocument;

        if (! $typeDocument) {
            return;
        }

        foreach ($typeDocument->champs_requis ?? [] as $field => $isRequired) {
            $value = $this->has($field) ? $this->input($field) : $demande->{$field};

            if ($isRequired && blank($value)) {
                $attribute = Str::of($field)->replace('_', ' ')->value();

                $validator->errors()->add($field, "Le champ {$attribute} est obligatoire pour ce type de document.");
            }
        }
    }

    private function validateEligibility(Validator $validator): void
    {
        $typeDocument = $this->demande()?->typeDocument;

        if (! $typeDocument || blank($typeDocument->eligibilite)) {
            return;
        }

        if ($this->input('statut') !== $typeDocument->eligibilite) {
            $validator->errors()->add(
                'statut',
                'Le statut sélectionné ne correspond pas à l’éligibilité de ce type de document.'
            );
        }
    }

    private function validateRequiredFiles(Validator $validator): void
    {
        $demande = $this->demande();
        $typeDocument = $demande?->typeDocument;

        if (! $demande || ! $typeDocument) {
            return;
        }

        $hasRequiredPieces = $typeDocument->piecesRequises()
            ->where('obligatoire', true)
            ->exists();

        if (! $hasRequiredPieces) {
            return;
        }

        if (! $this->hasFile('fichiers') && $demande->justificatifs()->doesntExist()) {
            $validator->errors()->add('fichiers', 'Veuillez joindre les pièces obligatoires pour ce type de document.');
        }
    }

    private function demande(): ?Demande
    {
        return Demande::with(['typeDocument.piecesRequises', 'justificatifs'])
            ->find($this->input('id'));
    }
}
