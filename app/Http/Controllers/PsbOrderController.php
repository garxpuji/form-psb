<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePsbOrderRequest;
use App\Models\Odp;
use App\Models\Paket;
use App\Models\PsbOrder;
use App\Models\Sale;
use App\Services\MikrotikProvisionerService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PsbOrderController extends Controller
{
    /**
     * Display the mobile-first technician PSB installation form.
     */
    public function create(Request $request, TelegramService $telegramService): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->loadMissing('telegramGroup');

        $targetGroup = null;
        try {
            $targetGroup = $telegramService->resolveTargetGroup($user);
        } catch (\Throwable) {
            $targetGroup = $user->telegramGroup;
        }

        $odps = Odp::query()
            ->orderBy('nama_odp')
            ->get();

        $pakets = Paket::query()
            ->orderBy('id')
            ->get();

        $salesList = Sale::query()
            ->with('ipRouter')
            ->orderBy('kode_sales_ip')
            ->get();

        return view('psb.create', [
            'user' => $user,
            'targetGroup' => $targetGroup,
            'isNonTeam' => $telegramService->isNonTeamUser($user),
            'odps' => $odps,
            'pakets' => $pakets,
            'salesList' => $salesList,
        ]);
    }

    /**
     * Store a new PSB installation order, run MikroTik auto-provisioning with failure fallback,
     * and broadcast the formatted installation report to Telegram.
     */
    public function store(
        StorePsbOrderRequest $request,
        MikrotikProvisionerService $provisioner,
        TelegramService $telegramService
    ): RedirectResponse {
        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var Odp $odp */
        $odp = Odp::findOrFail((int) $validated['odp_id']);

        $normalizedMac = MikrotikProvisionerService::normalizeMacAddress((string) $validated['mac_address']);
        $namaTeknisi = MikrotikProvisionerService::formatTechnicianName(
            $user->name,
            $validated['rekan_teknisi'] ?? null
        );
        $tipeKoneksi = $odp->status_upgrade === 'upgrade' ? 'pppoe' : 'static_ip';

        $order = PsbOrder::create([
            'user_id' => $user->id,
            'nama_pelanggan' => trim((string) $validated['nama_pelanggan']),
            'alamat' => trim((string) $validated['alamat']),
            'no_tlp' => trim((string) $validated['no_tlp']),
            'odp_id' => $odp->id,
            'port' => (int) $validated['port'],
            'paket_id' => (int) $validated['paket_id'],
            'mac_address' => $normalizedMac,
            'brand_router' => trim((string) $validated['brand_router']),
            'jarak_kabel' => trim((string) $validated['jarak_kabel']),
            'signal_odp' => trim((string) $validated['signal_odp']),
            'signal_ont' => trim((string) $validated['signal_ont']),
            'sales_id' => (int) $validated['sales_id'],
            'nama_teknisi' => $namaTeknisi,
            'tipe_koneksi' => $tipeKoneksi,
            'hasil_poe_ip' => null,
            'status_sync' => 'pending',
            'error_log' => null,
        ]);

        // Failure Fallback: Wrap MikroTik provisioning in try ... catch (\Throwable $e)
        try {
            $hasilPoeIp = $provisioner->provisionOrder($order);

            $order->update([
                'hasil_poe_ip' => $hasilPoeIp,
                'status_sync' => 'success',
                'error_log' => null,
            ]);
        } catch (\Throwable $e) {
            $order->update([
                'hasil_poe_ip' => null,
                'status_sync' => 'failed',
                'error_log' => $e->getMessage(),
            ]);
        }

        $order->load(['odp', 'paket', 'sales', 'user.telegramGroup']);

        // Always send Telegram report to target group (even if provisioning failed)
        $telegramSent = $telegramService->sendPsbReport($order, $user);

        return redirect()
            ->route('psb.create')
            ->with('psb_result', [
                'order_id' => $order->id,
                'status_sync' => $order->status_sync,
                'tipe_koneksi' => $order->tipe_koneksi,
                'hasil_poe_ip' => $order->status_sync === 'success'
                    ? $order->hasil_poe_ip
                    : TelegramService::FAILED_POE_IP_LABEL,
                'raw_poe_ip' => $order->hasil_poe_ip,
                'nama_pelanggan' => $order->nama_pelanggan,
                'odp_nama' => $order->odp?->nama_odp,
                'port' => $order->port,
                'paket_nama' => $order->paket?->nama_paket,
                'mac_address' => $order->mac_address,
                'sales_kode' => $order->sales?->kode_sales_ip,
                'nama_teknisi' => $order->nama_teknisi,
                'error_log' => $order->error_log,
                'telegram_sent' => $telegramSent,
            ]);
    }
}
