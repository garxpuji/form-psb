<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TelegramGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Normalize boolean flag and nullable thread_id.
     */
    protected function prepareForValidation(): void
    {
        $threadId = $this->input('thread_id');

        $this->merge([
            'is_all_team' => $this->boolean('is_all_team'),
            'thread_id' => ($threadId === '' || $threadId === null) ? null : (int) $threadId,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_grup' => ['required', 'string', 'max:150'],
            'chat_id' => ['required', 'string', 'max:100'],
            'thread_id' => ['nullable', 'integer'],
            'is_all_team' => ['required', 'boolean'],
        ];
    }
}
