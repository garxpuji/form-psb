<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderPhotoController extends Controller
{
    /**
     * Stream a private PSB installation photo (including customer KTP) strictly to authenticated NOC admins.
     */
    public function __invoke(PsbOrder $order, string $field): StreamedResponse
    {
        if (! in_array($field, array_keys(PsbOrder::PHOTO_FIELDS), true)) {
            abort(404, 'Jenis foto dokumentasi tidak ditemukan.');
        }

        $relativePath = trim((string) ($order->{$field} ?? ''));

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            abort(404, 'Berkas foto dokumentasi tidak tersedia.');
        }

        $diskName = null;
        if (Storage::disk('local')->exists($relativePath)) {
            $diskName = 'local';
        } elseif (Storage::disk('public')->exists($relativePath)) {
            $diskName = 'public';
        }

        if ($diskName === null) {
            abort(404, 'Berkas foto dokumentasi tidak ditemukan pada penyimpanan server.');
        }

        return Storage::disk($diskName)->response(
            $relativePath,
            basename($relativePath),
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'",
            ]
        );
    }
}
