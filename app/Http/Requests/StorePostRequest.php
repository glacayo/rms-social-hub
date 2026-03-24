<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // PagePolicy handles page-level auth
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'page_ids' => ['required', 'array', 'min:1'],
            'page_ids.*' => ['required', 'integer', 'exists:facebook_pages,id'],
            'post_type' => ['required', 'in:post,reel,story'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'media_type' => ['required', 'in:image,video,none'],
            'media' => ['nullable', 'file', 'max:1048576'], // 1GB in KB
        ];
    }

    public function messages(): array
    {
        return [
            'content.max' => 'El contenido no puede superar 2000 caracteres.',
            'page_ids.required' => 'Seleccioná al menos una página.',
            'page_ids.min' => 'Seleccioná al menos una página.',
            'page_ids.*.exists' => 'Una o más páginas seleccionadas no existen.',
            'post_type.in' => 'Tipo de post inválido. Opciones: post, reel, story.',
            'scheduled_at.after' => 'La fecha de publicación debe ser en el futuro.',
            'media.max' => 'El archivo no puede superar 1GB.',
        ];
    }
}
