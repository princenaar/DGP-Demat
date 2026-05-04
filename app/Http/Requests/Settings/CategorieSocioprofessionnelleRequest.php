<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategorieSocioprofessionnelleRequest extends FormRequest
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
        $categorie = $this->route('categorie');

        return [
            'libelle' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('categories_socioprofessionnelles', 'code')->ignore($categorie?->id)],
            'ordre' => ['required', 'integer', 'min:0'],
        ];
    }
}
