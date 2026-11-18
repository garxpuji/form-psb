<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePsbOrderRequest;
use App\Models\Odp;
use App\Models\Paket;
use App\Models\PsbOrder;
use App\Models\Sale;
use App\Models\User;
use App\Services\MikrotikProvisionerService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PsbOrderController extends Controller
{
    public function create(Request $request, TelegramService $telegramService): View
    {
        /** @var User $user */
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

    public function store(
        StorePsbOrderRequest $request,
        MikrotikProvisionerService $provisioner,
        TelegramService $telegramService
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        /** @var Odp $odp */
        $odp = Odp::findOrFail((int) $validated['odp_id']);

        $normalizedMac = MikrotikProvisionerService::normalizeMacAddress((string) $validated['mac_address']);
        $namaTeknisi = MikrotikProvisionerService::formatTechnicianName(
            $user->name,
            $validated['rekan_teknisi'] ?? null
        );
        $tipeKoneksi = $odp->status_upgrade === 'upgrade' ? 'pppoe' : 'static_ip';

        $storageDir = 'psb-photos/'.now()->format('Y-m');

        // Store customer documents & KTP strictly on the private local disk with EXIF stripped.
        $fotoMacOntPath = $this->storePrivateSanitizedPhoto($request->file('foto_mac_ont'), $storageDir);
        $fotoOdpPath = $this->storePrivateSanitizedPhoto($request->file('foto_odp'), $storageDir);
        $fotoKertasPath = $this->storePrivateSanitizedPhoto($request->file('foto_kertas_registrasi'), $storageDir);
        $fotoKtpPath = $this->storePrivateSanitizedPhoto($request->file('foto_ktp_pelanggan'), $storageDir);
        $fotoRumahPath = $this->storePrivateSanitizedPhoto($request->file('foto_rumah_pelanggan'), $storageDir);

        $order = PsbOrder::create([
            'user_id' => $user->id,
            'nama_pelanggan' => trim((string) $validated['nama_pelanggan']),
            'alamat' => trim((string) $validated['alamat']),
            'shareloc' => trim((string) $validated['shareloc']),
            'no_tlp' => trim((string) $validated['no_tlp']),
            'odp_id' => $odp->id,
            'port' => (int) $validated['port'],
            'paket_id' => (int) $validated['paket_id'],
            'mac_address' => $normalizedMac,
            'brand_router' => trim((string) $validated['brand_router']),
            'jarak_kabel' => trim((string) $validated['jarak_kabel']),
            'signal_odp' => trim((string) $validated['signal_odp']),
            'signal_ont' => trim((string) $validated['signal_ont']),
            'sales_id' => ! empty($validated['sales_id']) ? (int) $validated['sales_id'] : null,
            'nama_teknisi' => $namaTeknisi,
            'foto_mac_ont' => $fotoMacOntPath,
            'foto_odp' => $fotoOdpPath,
            'foto_kertas_registrasi' => $fotoKertasPath,
            'foto_ktp_pelanggan' => $fotoKtpPath,
            'foto_rumah_pelanggan' => $fotoRumahPath,
            'tipe_koneksi' => $tipeKoneksi,
            'hasil_poe_ip' => null,
            'status_sync' => 'pending',
            'error_log' => null,
        ]);

        // Save order as failed if RouterOS is unreachable so NOC still receives the Telegram alert.
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

        $telegramSent = $telegramService->sendPsbReport($order, $user);

        $psbResult = [
            'order_id' => $order->id,
            'status_sync' => $order->status_sync,
            'tipe_koneksi' => $order->tipe_koneksi,
            'hasil_poe_ip' => $order->status_sync === 'success'
                ? $order->hasil_poe_ip
                : TelegramService::FAILED_POE_IP_LABEL,
            'raw_poe_ip' => $order->hasil_poe_ip,
            'nama_pelanggan' => $order->nama_pelanggan,
            'shareloc' => $order->shareloc,
            'odp_nama' => $order->odp?->nama_odp,
            'port' => $order->port,
            'paket_nama' => $order->paket?->nama_paket,
            'mac_address' => $order->mac_address,
            'sales_kode' => $order->sales?->kode_sales_ip ?? 'TANPA SALES',
            'nama_teknisi' => $order->nama_teknisi,
            'error_log' => $order->error_log,
            'telegram_sent' => $telegramSent,
        ];

        if ($request->wantsJson()) {
            $request->session()->flash('psb_result', $psbResult);

            return response()->json([
                'ok' => true,
                'redirect' => route('psb.create'),
            ]);
        }

        return redirect()
            ->route('psb.create')
            ->with('psb_result', $psbResult);
    }

    /**
     * Re-encode uploaded image via GD when supported to strip EXIF metadata and polyglot payloads,
     * storing strictly on the non-public `local` disk.
     */
    private function storePrivateSanitizedPhoto(UploadedFile $file, string $storageDir): string
    {
        $realPath = $file->getRealPath();

        if ($realPath !== false && function_exists('imagecreatefromstring')) {
            $rawBytes = @file_get_contents($realPath);
            $image = $rawBytes !== false ? @imagecreatefromstring($rawBytes) : false;

            if ($image !== false) {
                ob_start();
                $encoded = @imagejpeg($image, null, 85);
                $cleanBytes = ob_get_clean();
                imagedestroy($image);

                if ($encoded && is_string($cleanBytes) && $cleanBytes !== '') {
                    $filename = $storageDir.'/'.Str::ulid()->toBase32().'.jpg';
                    Storage::disk('local')->put($filename, $cleanBytes);

                    return $filename;
                }
            }
        }

        return (string) $file->store($storageDir, 'local');
    }
}
