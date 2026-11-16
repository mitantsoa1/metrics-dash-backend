<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organizationName' => ['required', 'string', 'min:2', 'max:255'],
            'ownerName' => ['required', 'string', 'min:2', 'max:255'],
            'ownerEmail' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'organizationName.required' => 'Le nom de l\'organisation est requis.',
            'organizationName.min' => 'Le nom de l\'organisation doit contenir au moins 2 caractères.',
            'ownerName.required' => 'Votre nom est requis.',
            'ownerName.min' => 'Votre nom doit contenir au moins 2 caractères.',
            'ownerEmail.required' => 'L\'adresse e-mail est requise.',
            'ownerEmail.email' => 'L\'adresse e-mail est invalide.',
            'ownerEmail.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }
}
