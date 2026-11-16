<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StorePsbOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:150'],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_tlp' => ['required', 'string', 'max:30'],
            'odp_id' => ['required', 'integer', 'exists:odps,id'],
            'port' => ['required', 'integer', 'min:1', 'max:32'],
            'paket_id' => ['required', 'integer', 'exists:pakets,id'],
            'mac_address' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $hexOnly = preg_replace('/[^0-9A-Fa-f]/', '', (string) $value);
                    if (strlen((string) $hexOnly) !== 12) {
                        $fail('MAC Address harus terdiri dari tepat 12 karakter heksadesimal (0-9, A-F).');
                    }
                },
            ],
            'brand_router' => ['required', 'string', 'max:100'],
            'jarak_kabel' => ['required', 'string', 'max:50'],
            'signal_odp' => ['required', 'string', 'max:50'],
            'signal_ont' => ['required', 'string', 'max:50'],
            'sales_id' => ['required', 'integer', 'exists:sales,id'],
            'rekan_teknisi' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'alamat.required' => 'Alamat pemasangan wajib diisi.',
            'no_tlp.required' => 'Nomor telepon pelanggan wajib diisi.',
            'odp_id.required' => 'Silakan pilih ODP.',
            'odp_id.exists' => 'ODP yang dipilih tidak valid.',
            'port.required' => 'Nomor port ODP wajib diisi.',
            'port.min' => 'Nomor port minimal 1.',
            'port.max' => 'Nomor port maksimal 32.',
            'paket_id.required' => 'Silakan pilih paket internet.',
            'paket_id.exists' => 'Paket yang dipilih tidak valid.',
            'mac_address.required' => 'MAC Address ONT/Router wajib diisi.',
            'brand_router.required' => 'Brand router/ONT wajib diisi.',
            'jarak_kabel.required' => 'Jarak kabel wajib diisi.',
            'signal_odp.required' => 'Redaman / Signal ODP wajib diisi.',
            'signal_ont.required' => 'Redaman / Signal ONT wajib diisi.',
            'sales_id.required' => 'Silakan pilih kode sales.',
            'sales_id.exists' => 'Sales yang dipilih tidak valid.',
        ];
    }
}
