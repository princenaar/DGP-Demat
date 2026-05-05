<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'initial' => ['nullable', 'string', 'max:10'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(['ADMIN', 'ACCUEIL', 'CHEF_DE_DIVISION', 'AGENT', 'DRH'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $initial = trim((string) $this->input('initial'));

        $this->merge([
            'initial' => $initial === '' ? null : Str::upper(Str::ascii($initial)),
        ]);
    }
}
