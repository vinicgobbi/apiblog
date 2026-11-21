<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PageRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($this->route('page'))],
            'body_markdown' => 'required|string|max:200000',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Bloqueia slugs que colidem com rotas do front (config blog.reserved_page_slugs),
     * inclusive o que seria gerado a partir do título quando o slug não é informado.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $slug = $this->resultingSlug();

                if ($slug !== null && in_array($slug, config('blog.reserved_page_slugs'), true)) {
                    $validator->errors()->add('slug', 'Este endereço é usado pelo blog. Escolha outro slug');
                }
            },
        ];
    }

    /**
     * Slug que a página terá ao salvar, conforme o HasSlug do model: o informado
     * (já normalizado) ou, na criação e quando enviado vazio, o gerado do título.
     * Na edição sem o campo slug, o atual é mantido e não há o que validar.
     */
    private function resultingSlug(): ?string
    {
        if ($this->filled('slug')) {
            return (string) $this->input('slug');
        }

        $isCreating = $this->route('page') === null;

        if (! $isCreating && ! $this->has('slug')) {
            return null;
        }

        return is_string($this->input('title')) ? Str::slug($this->input('title')) : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório',
            'title.max' => 'O título pode ter no máximo :max caracteres',
            'slug.unique' => 'Já existe uma página com este slug',
            'body_markdown.required' => 'O conteúdo é obrigatório',
            'is_published.boolean' => 'O campo publicado deve ser verdadeiro ou falso',
        ];
    }
}
