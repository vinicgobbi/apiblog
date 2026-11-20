<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
     * O campo "website" é um honeypot: fica escondido no formulário e só é
     * preenchido por bots. Ele não é validado aqui para não revelar a
     * armadilha; o controller descarta o envio em silêncio.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author_name' => 'required|string|max:100',
            'author_email' => 'required|string|email|max:255',
            'body' => 'required|string|min:2|max:5000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'author_name.required' => 'O nome é obrigatório',
            'author_name.max' => 'O nome pode ter no máximo :max caracteres',
            'author_email.required' => 'O e-mail é obrigatório',
            'author_email.email' => 'Informe um e-mail válido',
            'body.required' => 'O comentário é obrigatório',
            'body.min' => 'O comentário deve ter no mínimo :min caracteres',
            'body.max' => 'O comentário pode ter no máximo :max caracteres',
        ];
    }

    public function isSpam(): bool
    {
        return $this->filled('website');
    }
}
