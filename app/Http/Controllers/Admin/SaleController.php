<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaleRequest;
use App\Models\Router;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Display the list of Sales codes and their mapped distribution routers.
     */
    public function index(): View
    {
        $salesList = Sale::query()
            ->with('ipRouter')
            ->withCount('psbOrders')
            ->orderBy('kode_sales_ip')
            ->get();

        $distributionRouters = Router::query()
            ->where('is_pppoe_server', false)
            ->orderBy('nama_router')
            ->get();

        return view('admin.sales.index', [
            'salesList' => $salesList,
            'distributionRouters' => $distributionRouters,
        ]);
    }

    /**
     * Store a newly created Sales record.
     */
    public function store(SaleRequest $request): RedirectResponse
    {
        $sale = Sale::create($request->validated());

        return redirect()
            ->route('admin.sales.index')
            ->with('success', "Sales {$sale->kode_sales_ip} ({$sale->nama_sales}) berhasil ditambahkan.");
    }

    /**
     * Update an existing Sales record.
     */
    public function update(SaleRequest $request, Sale $sale): RedirectResponse
    {
        $sale->update($request->validated());

        return redirect()
            ->route('admin.sales.index')
            ->with('success', "Sales {$sale->kode_sales_ip} berhasil diperbarui.");
    }

    /**
     * Delete a Sales record if not referenced by any PSB order.
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        if ($sale->psbOrders()->exists()) {
            return back()->with(
                'error',
                "Sales {$sale->kode_sales_ip} tidak dapat dihapus karena memiliki riwayat PSB Order."
            );
        }

        $kode = $sale->kode_sales_ip;
        $sale->delete();

        return redirect()
            ->route('admin.sales.index')
            ->with('success', "Sales {$kode} berhasil dihapus.");
    }
}
