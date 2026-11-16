<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RouterRequest;
use App\Models\Router;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RouterController extends Controller
{
    /**
     * Display the list of MikroTik routers.
     */
    public function index(): View
    {
        $routers = Router::query()
            ->with('sales')
            ->withCount([
                'ipSlots as available_slots_count' => fn ($q) => $q->where('status', 'available'),
                'ipSlots as used_slots_count' => fn ($q) => $q->where('status', 'used'),
            ])
            ->orderByDesc('is_pppoe_server')
            ->orderBy('nama_router')
            ->get();

        return view('admin.routers.index', [
            'routers' => $routers,
        ]);
    }

    /**
     * Store a newly created MikroTik router.
     */
    public function store(RouterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            if ($validated['is_pppoe_server']) {
                Router::where('is_pppoe_server', true)->update(['is_pppoe_server' => false]);
            }

            Router::create($validated);
        });

        return redirect()
            ->route('admin.routers.index')
            ->with('success', "Router {$validated['nama_router']} berhasil ditambahkan.");
    }

    /**
     * Update an existing MikroTik router (masking password on edit if left blank).
     */
    public function update(RouterRequest $request, Router $router): RedirectResponse
    {
        $validated = $request->validated();

        // Keep existing encrypted password when password field is left blank during edit
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        DB::transaction(function () use ($router, $validated): void {
            if ($validated['is_pppoe_server']) {
                Router::where('id', '!=', $router->id)
                    ->where('is_pppoe_server', true)
                    ->update(['is_pppoe_server' => false]);
            }

            $router->update($validated);
        });

        return redirect()
            ->route('admin.routers.index')
            ->with('success', "Router {$router->nama_router} berhasil diperbarui.");
    }

    /**
     * Delete a MikroTik router if it has no linked sales records.
     */
    public function destroy(Router $router): RedirectResponse
    {
        if ($router->sales()->exists()) {
            return back()->with(
                'error',
                "Router {$router->nama_router} tidak dapat dihapus karena masih digunakan oleh data Sales."
            );
        }

        $nama = $router->nama_router;
        $router->delete();

        return redirect()
            ->route('admin.routers.index')
            ->with('success', "Router {$nama} berhasil dihapus.");
    }
}
