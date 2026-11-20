<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCommentRequest extends FormRequest
{
    public const int PER_PAGE = 30;

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
            'status' => ['nullable', Rule::enum(CommentStatus::class)],
            'post_id' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'O status deve ser "pending", "approved", "rejected" ou "spam"',
            'post_id.integer' => 'O post informado é inválido',
        ];
    }
}
