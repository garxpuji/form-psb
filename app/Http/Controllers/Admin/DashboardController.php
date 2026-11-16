<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Models\Router;
use App\Models\Sale;
use App\Services\MikrotikProvisionerService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the NOC / Admin Dashboard with PSB Orders Log and Router Slot summary.
     */
    public function index(Request $request): View
    {
        $statusFilter = trim((string) $request->query('status_sync', ''));
        $koneksiFilter = trim((string) $request->query('tipe_koneksi', ''));
        $salesFilter = trim((string) $request->query('sales_id', ''));
        $search = trim((string) $request->query('q', ''));

        $ordersQuery = PsbOrder::query()
            ->with(['user', 'odp', 'paket', 'sales.ipRouter'])
            ->latest('id');

        if (in_array($statusFilter, ['success', 'failed', 'pending'], true)) {
            $ordersQuery->where('status_sync', $statusFilter);
        }

        if (in_array($koneksiFilter, ['pppoe', 'static_ip'], true)) {
            $ordersQuery->where('tipe_koneksi', $koneksiFilter);
        }

        if ($salesFilter === 'tanpa_sales') {
            $ordersQuery->whereNull('sales_id');
        } elseif ($salesFilter !== '' && is_numeric($salesFilter)) {
            $ordersQuery->where('sales_id', (int) $salesFilter);
        }

        if ($search !== '') {
            $ordersQuery->where(function ($q) use ($search): void {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                    ->orWhere('hasil_poe_ip', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%")
                    ->orWhere('nama_teknisi', 'like', "%{$search}%")
                    ->orWhere('no_tlp', 'like', "%{$search}%");
            });
        }

        $orders = $ordersQuery->paginate(15)->withQueryString();

        $stats = [
            'total' => PsbOrder::count(),
            'success' => PsbOrder::where('status_sync', 'success')->count(),
            'failed' => PsbOrder::where('status_sync', 'failed')->count(),
            'pending' => PsbOrder::where('status_sync', 'pending')->count(),
        ];

        $distributionRouters = Router::query()
            ->where('is_pppoe_server', false)
            ->with('sales')
            ->withCount([
                'ipSlots as available_slots_count' => fn ($q) => $q->where('status', 'available'),
                'ipSlots as used_slots_count' => fn ($q) => $q->where('status', 'used'),
            ])
            ->orderBy('nama_router')
            ->get();

        $salesList = Sale::query()->orderBy('kode_sales_ip')->get();

        return view('admin.dashboard', [
            'orders' => $orders,
            'stats' => $stats,
            'distributionRouters' => $distributionRouters,
            'salesList' => $salesList,
            'filters' => [
                'status_sync' => $statusFilter,
                'tipe_koneksi' => $koneksiFilter,
                'sales_id' => $salesFilter,
                'q' => $search,
            ],
        ]);
    }

    /**
     * Retry MikroTik auto-provisioning for a failed/pending PSB order and re-send the Telegram report.
     */
    public function retryProvisioning(
        PsbOrder $order,
        MikrotikProvisionerService $provisioner,
        TelegramService $telegramService
    ): RedirectResponse {
        $order->load(['odp', 'paket', 'sales.ipRouter', 'user.telegramGroup']);

        if ($order->odp !== null) {
            $order->tipe_koneksi = $order->odp->status_upgrade === 'upgrade' ? 'pppoe' : 'static_ip';
        }

        try {
            $hasilPoeIp = $provisioner->provisionOrder($order);

            $order->update([
                'tipe_koneksi' => $order->tipe_koneksi,
                'hasil_poe_ip' => $hasilPoeIp,
                'status_sync' => 'success',
                'error_log' => null,
            ]);

            $telegramService->sendPsbReport($order->fresh(), $order->user);

            return back()->with(
                'success',
                "Retry Provisioning berhasil untuk {$order->nama_pelanggan}. Hasil POE/IP: {$hasilPoeIp}"
            );
        } catch (\Throwable $e) {
            $order->update([
                'status_sync' => 'failed',
                'error_log' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                "Retry Provisioning gagal untuk {$order->nama_pelanggan}: {$e->getMessage()}"
            );
        }
    }

    /**
     * Trigger manual execution of `php artisan mikrotik:sync-slots` from the NOC Dashboard or Slot Monitor.
     */
    public function syncSlots(Request $request): RedirectResponse
    {
        $routerId = $request->input('router_id');
        $params = [];

        if ($routerId !== null && $routerId !== '') {
            $params['--router_id'] = (int) $routerId;
        }

        $exitCode = Artisan::call('mikrotik:sync-slots', $params);
        $output = trim(Artisan::output());

        if ($exitCode === 0) {
            return back()->with(
                'success',
                'Sinkronisasi slot MikroTik selesai dijalankan. ' . ($output !== '' ? "({$output})" : '')
            );
        }

        return back()->with(
            'error',
            'Sinkronisasi slot selesai dengan peringatan/error: ' . ($output !== '' ? $output : 'Cek koneksi RouterOS API.')
        );
    }
}
