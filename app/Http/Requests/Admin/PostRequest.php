<?php

namespace App\Http\Requests\Admin;

use App\Enums\PostStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Criação e edição de posts pelo admin.
 */
class PostRequest extends FormRequest
{
    use NormalizesSlug;

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
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($this->route('post'))],
            'excerpt' => 'nullable|string|max:500',
            'body_markdown' => 'required|string|max:200000',
            'category_id' => 'nullable|integer|exists:categories,id',
            'cover_image_path' => 'nullable|string|max:255',
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'nullable|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório',
            'title.max' => 'O título pode ter no máximo :max caracteres',
            'slug.unique' => 'Já existe um post com este slug',
            'excerpt.max' => 'O resumo pode ter no máximo :max caracteres',
            'body_markdown.required' => 'O conteúdo é obrigatório',
            'category_id.exists' => 'A categoria informada não existe',
            'status.required' => 'O status é obrigatório',
            'status.enum' => 'O status deve ser "draft" ou "published"',
            'published_at.date' => 'A data de publicação é inválida',
            'tags.max' => 'Um post pode ter no máximo :max tags',
            'tags.*.max' => 'Cada tag pode ter no máximo :max caracteres',
        ];
    }

    /**
     * Atributos do post. Ao publicar sem data, a publicação é imediata.
     *
     * @return array<string, mixed>
     */
    public function postAttributes(): array
    {
        $attributes = Arr::except($this->validated(), ['tags']);

        if ($attributes['status'] === PostStatus::Published->value && empty($attributes['published_at'])) {
            $attributes['published_at'] = now();
        }

        return $attributes;
    }

    /**
     * Nomes das tags enviados (vazios são descartados), ou null quando o
     * campo não foi enviado (nesse caso as tags atuais do post são mantidas).
     *
     * @return list<string>|null
     */
    public function tagNames(): ?array
    {
        if (! $this->has('tags')) {
            return null;
        }

        return array_values(array_filter($this->validated('tags') ?? [], fn (?string $name): bool => filled($name)));
    }
}
