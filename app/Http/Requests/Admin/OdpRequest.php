<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Odp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OdpRequest extends FormRequest
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
        $routeOdp = $this->route('odp');
        $ignoreId = $routeOdp instanceof Odp ? $routeOdp->id : $routeOdp;

        return [
            'nama_odp' => [
                'required',
                'string',
                'max:100',
                Rule::unique('odps', 'nama_odp')->ignore($ignoreId),
            ],
            'kode_odp' => ['required', 'string', 'max:20'],
            'status_upgrade' => ['required', Rule::in(['upgrade', 'belum_upgrade'])],
            'last_seq' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
