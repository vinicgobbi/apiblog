<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Moderação: apenas o status do comentário pode ser alterado; o texto do
 * leitor nunca é editado.
 */
class UpdateCommentRequest extends FormRequest
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
            'status' => ['required', Rule::enum(CommentStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório',
            'status.enum' => 'O status deve ser "pending", "approved", "rejected" ou "spam"',
        ];
    }

    public function status(): CommentStatus
    {
        return CommentStatus::from($this->validated('status'));
    }
}
