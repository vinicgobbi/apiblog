<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;

/**
 * Normaliza o slug enviado antes da validação, para que a regra unique
 * compare o valor que será de fato gravado (ex.: "Meu Post" → "meu-post").
 */
trait NormalizesSlug
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('slug')) ?: null]);
        }
    }
}
