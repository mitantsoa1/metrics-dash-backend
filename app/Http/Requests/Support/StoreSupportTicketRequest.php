<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:150'],
            'role' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'min:5', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Votre nom est obligatoire.',
            'name.min' => 'Le nom doit comporter au moins 2 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'role.required' => 'Le rôle professionnel est requis.',
            'subject.required' => 'L\'objet de la demande est obligatoire.',
            'subject.min' => 'L\'objet doit contenir au moins 5 caractères.',
            'message.required' => 'Le message de votre demande est obligatoire.',
            'message.min' => 'Le message doit contenir au moins 10 caractères.',
        ];
    }
}
