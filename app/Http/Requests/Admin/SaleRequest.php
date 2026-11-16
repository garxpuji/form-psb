<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Normalize uppercase sales code before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('kode_sales_ip')) {
            $this->merge([
                'kode_sales_ip' => strtoupper(trim((string) $this->input('kode_sales_ip'))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_sales' => ['required', 'string', 'max:150'],
            'kode_sales_ip' => ['required', 'string', 'max:20'],
            'kode_sales_pppoe' => ['required', 'string', 'max:10'],
            'ip_router_id' => ['required', 'integer', 'exists:routers,id'],
        ];
    }
}
