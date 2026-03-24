<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Only draft posts can be fully updated
        // Scheduled posts can only change scheduled_at
        return [
            'content' => ['sometimes', 'string', 'max:2000'],
            'page_ids' => ['sometimes', 'array', 'min:1'],
            'page_ids.*' => ['integer', 'exists:facebook_pages,id'],
            'post_type' => ['sometimes', 'in:post,reel,story'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'media_type' => ['sometimes', 'in:image,video,none'],
            'media' => ['nullable', 'file', 'max:1048576'],
        ];
    }
}
