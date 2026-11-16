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
     * Normalize nullable sales_id before validation.
     */
    protected function prepareForValidation(): void
    {
        $salesId = $this->input('sales_id');
        if ($salesId === '' || $salesId === 'tanpa_sales') {
            $this->merge(['sales_id' => null]);
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
            'nama_pelanggan' => ['required', 'string', 'max:150'],
            'alamat' => ['required', 'string', 'max:1000'],
            'shareloc' => ['required', 'string', 'max:500'],
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
            'sales_id' => ['nullable', 'integer', 'exists:sales,id'],
            'rekan_teknisi' => ['nullable', 'string', 'max:100'],
            'foto_mac_ont' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'foto_odp' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'foto_kertas_registrasi' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'foto_ktp_pelanggan' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'foto_rumah_pelanggan' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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
            'shareloc.required' => 'Link Shareloc / koordinat lokasi pelanggan wajib disertakan.',
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
            'foto_mac_ont.required' => 'Foto MAC ONT wajib dilampirkan.',
            'foto_mac_ont.image' => 'File Foto MAC ONT harus berupa gambar (JPG/PNG/WEBP).',
            'foto_mac_ont.max' => 'Ukuran Foto MAC ONT maksimal 10 MB.',
            'foto_odp.required' => 'Foto ODP wajib dilampirkan.',
            'foto_odp.image' => 'File Foto ODP harus berupa gambar (JPG/PNG/WEBP).',
            'foto_odp.max' => 'Ukuran Foto ODP maksimal 10 MB.',
            'foto_kertas_registrasi.required' => 'Foto Kertas Registrasi wajib dilampirkan.',
            'foto_kertas_registrasi.image' => 'File Foto Kertas Registrasi harus berupa gambar (JPG/PNG/WEBP).',
            'foto_kertas_registrasi.max' => 'Ukuran Foto Kertas Registrasi maksimal 10 MB.',
            'foto_ktp_pelanggan.required' => 'Foto KTP Pelanggan wajib dilampirkan.',
            'foto_ktp_pelanggan.image' => 'File Foto KTP Pelanggan harus berupa gambar (JPG/PNG/WEBP).',
            'foto_ktp_pelanggan.max' => 'Ukuran Foto KTP Pelanggan maksimal 10 MB.',
            'foto_rumah_pelanggan.required' => 'Foto Rumah Pelanggan wajib dilampirkan.',
            'foto_rumah_pelanggan.image' => 'File Foto Rumah Pelanggan harus berupa gambar (JPG/PNG/WEBP).',
            'foto_rumah_pelanggan.max' => 'Ukuran Foto Rumah Pelanggan maksimal 10 MB.',
        ];
    }
}
