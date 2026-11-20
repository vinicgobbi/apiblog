<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexPostRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 20;

    public const int MAX_PER_PAGE = 100;

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
            'state' => 'nullable|in:draft,scheduled,published',
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
            'state.in' => 'O filtro de situação deve ser "draft", "scheduled" ou "published"',
            'q.max' => 'A busca pode ter no máximo :max caracteres',
            'per_page.max' => 'A quantidade por página deve ser de no máximo :max',
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', self::DEFAULT_PER_PAGE);
    }
}
