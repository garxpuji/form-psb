<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RouterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Prepare inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_pppoe_server' => $this->boolean('is_pppoe_server'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCreating = $this->isMethod('POST');

        return [
            'nama_router' => ['required', 'string', 'max:150'],
            'ip_host' => ['required', 'string', 'max:100'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:100'],
            'password' => [$isCreating ? 'required' : 'nullable', 'string', 'max:255'],
            'lan_interface' => [
                Rule::requiredIf(! $this->boolean('is_pppoe_server')),
                'nullable',
                'string',
                'max:100',
            ],
            'is_pppoe_server' => ['required', 'boolean'],
        ];
    }
}
