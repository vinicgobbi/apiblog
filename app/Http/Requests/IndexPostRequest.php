<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexPostRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 10;

    public const int MAX_PER_PAGE = 50;

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
            'tag' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:'.self::MAX_PER_PAGE,
            'page' => 'nullable|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.max' => 'A busca pode ter no máximo :max caracteres',
            'per_page.integer' => 'A quantidade por página deve ser um número inteiro',
            'per_page.min' => 'A quantidade por página deve ser de no mínimo :min',
            'per_page.max' => 'A quantidade por página deve ser de no máximo :max',
            'page.integer' => 'A página deve ser um número inteiro',
            'page.min' => 'A página deve ser no mínimo :min',
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', self::DEFAULT_PER_PAGE);
    }
}
