<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Closure;
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
            'ip_host' => trim((string) $this->input('ip_host')),
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
            'ip_host' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $host = strtolower(trim((string) $value));

                    if (in_array($host, ['localhost', '0.0.0.0', '::1', '[::1]'], true)) {
                        $fail('Host router tidak boleh mengarah ke alamat loopback atau lokal server.');

                        return;
                    }

                    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                        if (str_starts_with($host, '127.') || str_starts_with($host, '169.254.')) {
                            $fail('Alamat IP loopback (127.0.0.0/8) atau link-local metadata (169.254.0.0/16) tidak diizinkan.');
                        }

                        return;
                    }

                    if (preg_match('/^(?=.{1,100}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $host) !== 1) {
                        $fail('Masukkan alamat IPv4/IPv6 atau nama domain (FQDN) router yang valid.');
                    }
                },
            ],
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
