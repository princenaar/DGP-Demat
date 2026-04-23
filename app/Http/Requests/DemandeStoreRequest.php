<?php

namespace App\Http\Requests;

use App\Models\TypeDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_document_id' => ['required', 'exists:type_documents,id'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'nin' => ['required', 'regex:/^[0-9]{13}$/'],
            'statut' => ['required', 'string', 'in:étatique,contractuel'],
            'matricule' => ['nullable', 'required_if:statut,étatique', 'regex:/^[0-9]{6}[A-Za-z]$/'],
            'structure_id' => ['required', 'exists:structures,id'],

            'email' => ['required', 'email:rfc', 'max:255'],
            'telephone' => ['required', 'regex:/^\+221 [0-9]{2} [0-9]{3} [0-9]{2} [0-9]{2}$/'],

            'categorie_socioprofessionnelle' => ['nullable', 'string', 'max:150'],
            'date_prise_service' => ['nullable', 'date'],
            'date_fin_service' => ['nullable', 'date', 'after_or_equal:date_prise_service'],
            'date_depart_retraite' => ['nullable', 'date'],

            'fichiers' => ['nullable', 'array', 'max:5'],
            'fichiers.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'g-recaptcha-response' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateTypeDocumentFields($validator);
                $this->validateRecaptcha($validator);
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
            'g-recaptcha-response.required' => 'Veuillez valider le reCAPTCHA.',
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
            'g-recaptcha-response' => 'reCAPTCHA',
            'date_prise_service' => 'date de prise de service',
            'date_fin_service' => 'date de fin de service',
            'date_depart_retraite' => 'date de départ à la retraite',
            'categorie_socioprofessionnelle' => 'catégorie socio-professionnelle',
            'fichiers.*' => 'fichier',
        ];
    }

    private function validateTypeDocumentFields(Validator $validator): void
    {
        $typeDocument = TypeDocument::find($this->input('type_document_id'));

        if (! $typeDocument) {
            return;
        }

        foreach ($typeDocument->champs_requis ?? [] as $field => $isRequired) {
            if ($isRequired && blank($this->input($field))) {
                $attribute = $this->attributes()[$field] ?? Str::of($field)->replace('_', ' ')->value();

                $validator->errors()->add($field, "Le champ {$attribute} est obligatoire pour ce type de document.");
            }
        }
    }

    private function validateRecaptcha(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $secretKey = config('services.recaptcha.secret_key');

        if (blank($secretKey)) {
            $validator->errors()->add('g-recaptcha-response', 'La vérification reCAPTCHA n\'est pas configurée.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $this->input('g-recaptcha-response'),
                    'remoteip' => $this->ip(),
                ]);
        } catch (ConnectionException) {
            $validator->errors()->add('g-recaptcha-response', 'La vérification reCAPTCHA est momentanément indisponible.');

            return;
        }

        if (! $response->json('success', false)) {
            $validator->errors()->add('g-recaptcha-response', 'La vérification reCAPTCHA a échoué.');
        }
    }
}
