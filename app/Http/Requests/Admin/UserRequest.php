<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Normalize boolean is_active and nullable telegram_group_id.
     */
    protected function prepareForValidation(): void
    {
        $groupId = $this->input('telegram_group_id');

        $this->merge([
            'username' => strtolower(trim((string) $this->input('username'))),
            'is_active' => $this->boolean('is_active', true),
            'telegram_group_id' => ($groupId === '' || $groupId === null) ? null : (int) $groupId,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $ignoreId = $routeUser instanceof User ? $routeUser->id : $routeUser;
        $isCreating = $this->isMethod('POST');
        $minPasswordLength = $this->input('role') === 'admin' ? 12 : 8;

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($ignoreId),
            ],
            'password' => [$isCreating ? 'required' : 'nullable', 'string', "min:{$minPasswordLength}", 'max:255'],
            'role' => ['required', Rule::in(['admin', 'teknisi', 'umum'])],
            'telegram_group_id' => ['nullable', 'integer', 'exists:telegram_groups,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $minPasswordLength = $this->input('role') === 'admin' ? 12 : 8;

        return [
            'password.min' => "Password minimal terdiri dari {$minPasswordLength} karakter.",
            'username.unique' => 'Username tersebut sudah digunakan oleh akun lain.',
        ];
    }
}
