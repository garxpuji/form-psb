<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaketRequest;
use App\Models\Paket;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaketController extends Controller
{
    /**
     * Display the list of bandwidth packages.
     */
    public function index(): View
    {
        $pakets = Paket::query()
            ->withCount('psbOrders')
            ->orderBy('id')
            ->get();

        return view('admin.pakets.index', [
            'pakets' => $pakets,
        ]);
    }

    /**
     * Store a newly created Paket.
     */
    public function store(PaketRequest $request): RedirectResponse
    {
        $paket = Paket::create($request->validated());

        return redirect()
            ->route('admin.pakets.index')
            ->with('success', "Paket {$paket->nama_paket} berhasil ditambahkan.");
    }

    /**
     * Update an existing Paket.
     */
    public function update(PaketRequest $request, Paket $paket): RedirectResponse
    {
        $paket->update($request->validated());

        return redirect()
            ->route('admin.pakets.index')
            ->with('success', "Paket {$paket->nama_paket} berhasil diperbarui.");
    }

    /**
     * Delete a Paket if not referenced by any PSB order.
     */
    public function destroy(Paket $paket): RedirectResponse
    {
        if ($paket->psbOrders()->exists()) {
            return back()->with(
                'error',
                "Paket {$paket->nama_paket} tidak dapat dihapus karena memiliki riwayat PSB Order."
            );
        }

        $nama = $paket->nama_paket;
        $paket->delete();

        return redirect()
            ->route('admin.pakets.index')
            ->with('success', "Paket {$nama} berhasil dihapus.");
    }
}
