<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public const int MAX_SIZE_KB = 5120;

    public const int MAX_DIMENSION = 8000;

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
     * O tipo é verificado pelo conteúdo do arquivo (não pela extensão enviada).
     * SVGs são aceitos e limpos de scripts no upload (ver ImageSanitizer). O
     * limite de dimensões evita imagens que consomem memória demais ao serem
     * processadas (não se aplica a SVG, que é vetorial).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,gif,svg',
                'max:'.self::MAX_SIZE_KB,
                'dimensions:max_width='.self::MAX_DIMENSION.',max_height='.self::MAX_DIMENSION,
            ],
            'alt' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Selecione uma imagem',
            'file.file' => 'O envio da imagem falhou',
            'file.mimes' => 'A imagem deve ser JPG, PNG, WebP, GIF ou SVG',
            'file.max' => 'A imagem pode ter no máximo 5 MB',
            'file.dimensions' => 'A imagem pode ter no máximo '.self::MAX_DIMENSION.'×'.self::MAX_DIMENSION.' pixels',
            'alt.max' => 'O texto alternativo pode ter no máximo :max caracteres',
        ];
    }
}
