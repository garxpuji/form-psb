<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_paket' => ['required', 'string', 'max:100'],
            'queue_max_limit' => ['required', 'string', 'max:50'],
            'pppoe_profile' => ['required', 'string', 'max:100'],
        ];
    }
}
