<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OdpRequest;
use App\Models\Odp;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OdpController extends Controller
{
    /**
     * Display the list of ODPs with 1-click status_upgrade toggle.
     */
    public function index(): View
    {
        $odps = Odp::query()
            ->withCount('psbOrders')
            ->orderBy('nama_odp')
            ->get();

        return view('admin.odps.index', [
            'odps' => $odps,
        ]);
    }

    /**
     * Store a newly created ODP.
     */
    public function store(OdpRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['last_seq'] = (int) ($validated['last_seq'] ?? 0);

        $odp = Odp::create($validated);

        return redirect()
            ->route('admin.odps.index')
            ->with('success', "ODP {$odp->nama_odp} berhasil ditambahkan.");
    }

    /**
     * Update an existing ODP.
     */
    public function update(OdpRequest $request, Odp $odp): RedirectResponse
    {
        $validated = $request->validated();
        $validated['last_seq'] = (int) ($validated['last_seq'] ?? $odp->last_seq);

        $odp->update($validated);

        return redirect()
            ->route('admin.odps.index')
            ->with('success', "ODP {$odp->nama_odp} berhasil diperbarui.");
    }

    /**
     * 1-click toggle between 'belum_upgrade' (Static IP) and 'upgrade' (PPPoE).
     */
    public function toggleUpgrade(Odp $odp): RedirectResponse
    {
        $newStatus = $odp->status_upgrade === 'upgrade' ? 'belum_upgrade' : 'upgrade';
        $odp->update(['status_upgrade' => $newStatus]);

        $label = $newStatus === 'upgrade' ? 'UPGRADE [PPPoE]' : 'BELUM UPGRADE [Static IP]';

        return back()->with(
            'success',
            "Status ODP {$odp->nama_odp} berhasil diubah menjadi {$label}."
        );
    }

    /**
     * Delete an ODP if not referenced by any PSB order.
     */
    public function destroy(Odp $odp): RedirectResponse
    {
        if ($odp->psbOrders()->exists()) {
            return back()->with(
                'error',
                "ODP {$odp->nama_odp} tidak dapat dihapus karena memiliki riwayat PSB Order."
            );
        }

        $nama = $odp->nama_odp;
        $odp->delete();

        return redirect()
            ->route('admin.odps.index')
            ->with('success', "ODP {$nama} berhasil dihapus.");
    }
}
