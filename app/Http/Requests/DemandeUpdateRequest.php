<?php

namespace App\Http\Requests;

use App\Models\Demande;
use App\Models\EtatDemande;
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
        return $this->demande()?->etatDemande?->nom === EtatDemande::COMPLEMENTS;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_document_id' => ['required', 'integer'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'nin' => ['required', 'regex:/^[0-9]{13}$/'],
            'statut' => ['required', 'string', 'in:étatique,contractuel'],
            'matricule' => ['nullable', 'required_if:statut,étatique', 'regex:/^[0-9]{6}[A-Za-z]$/'],
            'structure_id' => ['required', 'exists:structures,id'],

            'telephone' => ['required', 'regex:/^\+221 [0-9]{2} [0-9]{3} [0-9]{2} [0-9]{2}$/'],

            'categorie_socioprofessionnelle_id' => ['nullable', 'exists:categories_socioprofessionnelles,id'],
            'date_prise_service' => ['nullable', 'date'],
            'date_fin_service' => ['nullable', 'date', 'after_or_equal:date_prise_service'],
            'date_depart_retraite' => ['nullable', 'date'],

            'fichiers' => ['nullable', 'array', 'max:5'],
            'fichiers.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateTypeDocumentFields($validator);
                $this->validateTypeDocumentIntegrity($validator);
                $this->validateEligibility($validator);
                $this->validateRequiredFiles($validator);
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nin.regex' => 'Le NIN doit contenir exactement 13 chiffres.',
            'matricule.regex' => 'Le matricule doit respecter le format 000000A.',
            'telephone.regex' => 'Le téléphone doit respecter le format +221 00 000 00 00.',
            'fichiers.max' => 'Vous pouvez joindre au maximum 5 fichiers.',
            'fichiers.*.mimes' => 'Les fichiers doivent être au format PDF, JPG, JPEG ou PNG.',
            'fichiers.*.max' => 'Chaque fichier ne doit pas dépasser 5 Mo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type_document_id' => 'type de document',
            'structure_id' => 'structure',
            'nin' => 'NIN',
            'date_prise_service' => 'date de prise de service',
            'date_fin_service' => 'date de fin de service',
            'date_depart_retraite' => 'date de départ à la retraite',
            'categorie_socioprofessionnelle_id' => 'catégorie socio-professionnelle',
            'fichiers.*' => 'fichier',
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
                $attribute = $this->attributes()[$field] ?? Str::of($field)->replace('_', ' ')->value();

                $validator->errors()->add($field, "Le champ {$attribute} est obligatoire pour ce type de document.");
            }
        }
    }

    private function validateTypeDocumentIntegrity(Validator $validator): void
    {
        $demande = $this->demande();

        if (! $demande || (int) $this->input('type_document_id') === $demande->type_document_id) {
            return;
        }

        $validator->errors()->add('type_document_id', 'Le type de document ne peut pas être modifié.');
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
        $demande = $this->route('demande');

        if ($demande instanceof Demande) {
            return $demande->loadMissing(['typeDocument.piecesRequises', 'justificatifs', 'etatDemande']);
        }

        return Demande::with(['typeDocument.piecesRequises', 'justificatifs'])
            ->find($demande);
    }
}
