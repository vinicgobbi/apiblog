<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderPagesRequest extends FormRequest
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
            'ids' => 'required|array',
            'ids.*' => 'required|integer|distinct',
        ];
    }

    /**
     * Exige todas as páginas existentes, cada uma uma vez: uma lista desatualizada
     * (ex.: outra aba criou ou excluiu uma página) não pode deixar a ordem pela metade.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $sent = collect($this->ids())->sort()->values()->all();
                $existing = Page::query()->orderBy('id')->pluck('id')->all();

                if ($sent !== $existing) {
                    $validator->errors()->add('ids', 'A lista de páginas está desatualizada. Recarregue e tente novamente');
                }
            },
        ];
    }

    /**
     * Ids das páginas na nova ordem.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return array_map(intval(...), array_values($this->input('ids', [])));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Informe a ordem das páginas',
            'ids.array' => 'Informe a ordem das páginas',
            'ids.*.integer' => 'Página inválida',
            'ids.*.distinct' => 'Uma página não pode aparecer duas vezes',
        ];
    }
}
