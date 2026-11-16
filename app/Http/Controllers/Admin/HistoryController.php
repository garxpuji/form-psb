<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PsbOrder;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->extractFilters($request);

        $periodBounds = $this->resolvePeriodBounds($filters);

        $periodBaseQuery = PsbOrder::query();
        $this->applyPeriodAndCommonFilters($periodBaseQuery, $filters, $periodBounds);

        $ordersQuery = (clone $periodBaseQuery)
            ->with(['user', 'odp', 'paket', 'sales.ipRouter'])
            ->latest('created_at')
            ->latest('id');

        $this->applySalesFilter($ordersQuery, $filters['filter_sales']);

        $filteredSummaryQuery = clone $periodBaseQuery;
        $this->applySalesFilter($filteredSummaryQuery, $filters['filter_sales']);

        $summary = [
            'total' => (clone $filteredSummaryQuery)->count(),
            'dengan_sales' => (clone $filteredSummaryQuery)->whereNotNull('sales_id')->count(),
            'tanpa_sales' => (clone $filteredSummaryQuery)->whereNull('sales_id')->count(),
            'pppoe' => (clone $filteredSummaryQuery)->where('tipe_koneksi', 'pppoe')->count(),
            'static_ip' => (clone $filteredSummaryQuery)->where('tipe_koneksi', 'static_ip')->count(),
            'success' => (clone $filteredSummaryQuery)->where('status_sync', 'success')->count(),
            'failed' => (clone $filteredSummaryQuery)->where('status_sync', 'failed')->count(),
        ];

        $salesList = Sale::query()->orderBy('kode_sales_ip')->get();
        $rawGrouped = (clone $periodBaseQuery)
            ->selectRaw("
                sales_id,
                COUNT(*) as total_orders,
                SUM(CASE WHEN tipe_koneksi = 'pppoe' THEN 1 ELSE 0 END) as total_pppoe,
                SUM(CASE WHEN tipe_koneksi = 'static_ip' THEN 1 ELSE 0 END) as total_static_ip,
                SUM(CASE WHEN status_sync = 'success' THEN 1 ELSE 0 END) as total_success,
                SUM(CASE WHEN status_sync = 'failed' THEN 1 ELSE 0 END) as total_failed
            ")
            ->groupBy('sales_id')
            ->get()
            ->keyBy(fn ($row) => $row->sales_id === null ? 'tanpa_sales' : (string) $row->sales_id);

        $rekapRows = [];

        foreach ($salesList as $sale) {
            $stat = $rawGrouped->get((string) $sale->id);
            $rekapRows[] = [
                'filter_key' => (string) $sale->id,
                'kode' => $sale->kode_sales_ip,
                'label' => $sale->nama_sales,
                'is_tanpa_sales' => false,
                'total' => (int) ($stat->total_orders ?? 0),
                'pppoe' => (int) ($stat->total_pppoe ?? 0),
                'static_ip' => (int) ($stat->total_static_ip ?? 0),
                'success' => (int) ($stat->total_success ?? 0),
                'failed' => (int) ($stat->total_failed ?? 0),
            ];
        }

        $tanpaSalesStat = $rawGrouped->get('tanpa_sales');
        $rekapRows[] = [
            'filter_key' => 'tanpa_sales',
            'kode' => 'TANPA SALES',
            'label' => 'Langsung / Kantor (Non-Sales)',
            'is_tanpa_sales' => true,
            'total' => (int) ($tanpaSalesStat->total_orders ?? 0),
            'pppoe' => (int) ($tanpaSalesStat->total_pppoe ?? 0),
            'static_ip' => (int) ($tanpaSalesStat->total_static_ip ?? 0),
            'success' => (int) ($tanpaSalesStat->total_success ?? 0),
            'failed' => (int) ($tanpaSalesStat->total_failed ?? 0),
        ];

        $orders = $ordersQuery->paginate(20)->withQueryString();

        return view('admin.history.index', [
            'orders' => $orders,
            'summary' => $summary,
            'rekapRows' => $rekapRows,
            'salesList' => $salesList,
            'filters' => $filters,
            'periodLabel' => $periodBounds['label'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->extractFilters($request);
        $periodBounds = $this->resolvePeriodBounds($filters);

        $query = PsbOrder::query()
            ->with(['odp', 'paket', 'sales'])
            ->latest('created_at')
            ->latest('id');

        $this->applyPeriodAndCommonFilters($query, $filters, $periodBounds);
        $this->applySalesFilter($query, $filters['filter_sales']);

        $filename = 'rekap-psb-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // Prepend UTF-8 BOM so Microsoft Excel detects column encoding cleanly.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID Order',
                'Tanggal Input',
                'Nama Pelanggan',
                'No. Telepon',
                'Alamat',
                'Shareloc',
                'Kategori Sales',
                'Kode Sales',
                'ODP',
                'Port',
                'Paket',
                'Tipe Koneksi',
                'Hasil POE / IP',
                'MAC Address',
                'Brand Router',
                'Jarak Kabel',
                'Signal ODP',
                'Signal ONT',
                'Nama Teknisi',
                'Status Sync',
            ]);

            foreach ($query->cursor() as $order) {
                fputcsv($handle, [
                    $order->id,
                    $order->created_at?->format('Y-m-d H:i:s'),
                    $order->nama_pelanggan,
                    $order->no_tlp,
                    $order->alamat,
                    $order->shareloc,
                    $order->sales_id !== null ? 'DENGAN SALES' : 'TANPA SALES',
                    $order->sales?->kode_sales_ip ?? 'TANPA SALES',
                    $order->odp?->nama_odp ?? '-',
                    $order->port,
                    $order->paket?->nama_paket ?? '-',
                    strtoupper($order->tipe_koneksi),
                    $order->hasil_poe_ip ?? 'GAGAL AUTO-CONFIG',
                    $order->mac_address,
                    $order->brand_router,
                    $order->jarak_kabel,
                    $order->signal_odp,
                    $order->signal_ont,
                    $order->nama_teknisi,
                    strtoupper($order->status_sync),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(Request $request): array
    {
        $periode = trim((string) $request->query('periode', 'semua'));
        if (! in_array($periode, ['semua', 'tanggal', 'harian', 'mingguan', 'bulanan', 'tahunan'], true)) {
            $periode = 'semua';
        }

        return [
            'periode' => $periode,
            'tanggal_harian' => trim((string) $request->query('tanggal_harian', now()->toDateString())),
            'tanggal_mulai' => trim((string) $request->query('tanggal_mulai', now()->startOfMonth()->toDateString())),
            'tanggal_selesai' => trim((string) $request->query('tanggal_selesai', now()->toDateString())),
            'minggu' => trim((string) $request->query('minggu', now()->format('o-\WW'))),
            'bulan' => trim((string) $request->query('bulan', (string) now()->month)),
            'tahun' => trim((string) $request->query('tahun', (string) now()->year)),
            'filter_sales' => trim((string) $request->query('filter_sales', '')),
            'status_sync' => trim((string) $request->query('status_sync', '')),
            'tipe_koneksi' => trim((string) $request->query('tipe_koneksi', '')),
            'q' => trim((string) $request->query('q', '')),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{start: ?Carbon, end: ?Carbon, label: string}
     */
    private function resolvePeriodBounds(array $filters): array
    {
        $periode = $filters['periode'];

        try {
            if ($periode === 'harian') {
                $date = Carbon::parse($filters['tanggal_harian']);
                return [
                    'start' => $date->copy()->startOfDay(),
                    'end' => $date->copy()->endOfDay(),
                    'label' => 'Harian: ' . $date->format('d/m/Y'),
                ];
            }

            if ($periode === 'tanggal') {
                $start = $filters['tanggal_mulai'] !== ''
                    ? Carbon::parse($filters['tanggal_mulai'])->startOfDay()
                    : now()->startOfMonth()->startOfDay();
                $end = $filters['tanggal_selesai'] !== ''
                    ? Carbon::parse($filters['tanggal_selesai'])->endOfDay()
                    : $start->copy()->endOfDay();

                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }

                return [
                    'start' => $start,
                    'end' => $end,
                    'label' => 'Rentang Tanggal: ' . $start->format('d/m/Y') . ' s/d ' . $end->format('d/m/Y'),
                ];
            }

            if ($periode === 'mingguan') {
                if (preg_match('/^(\d{4})-W(\d{1,2})$/', $filters['minggu'], $matches) === 1) {
                    $year = (int) $matches[1];
                    $week = (int) $matches[2];
                    $start = Carbon::now()->setISODate($year, $week)->startOfWeek();
                    $end = $start->copy()->endOfWeek();
                } else {
                    $start = now()->startOfWeek();
                    $end = now()->endOfWeek();
                }

                return [
                    'start' => $start,
                    'end' => $end,
                    'label' => sprintf(
                        'Mingguan (%s): %s s/d %s',
                        $filters['minggu'],
                        $start->format('d/m/Y'),
                        $end->format('d/m/Y')
                    ),
                ];
            }

            if ($periode === 'bulanan') {
                $month = max(1, min(12, (int) $filters['bulan']));
                $year = max(2020, min(2100, (int) $filters['tahun']));
                $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
                $end = $start->copy()->endOfMonth()->endOfDay();

                $namaBulan = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ][$month];

                return [
                    'start' => $start,
                    'end' => $end,
                    'label' => "Bulanan: {$namaBulan} {$year}",
                ];
            }

            if ($periode === 'tahunan') {
                $year = max(2020, min(2100, (int) $filters['tahun']));
                $start = Carbon::createFromDate($year, 1, 1)->startOfDay();
                $end = Carbon::createFromDate($year, 12, 31)->endOfDay();

                return [
                    'start' => $start,
                    'end' => $end,
                    'label' => "Tahunan: Tahun {$year}",
                ];
            }
        } catch (\Throwable) {
            // Ignore malformed date query strings and fall back to all time.
        }

        return [
            'start' => null,
            'end' => null,
            'label' => 'Semua Periode Waktu',
        ];
    }

    /**
     * @param  Builder<PsbOrder>  $query
     * @param  array<string, string>  $filters
     * @param  array{start: ?Carbon, end: ?Carbon, label: string}  $periodBounds
     */
    private function applyPeriodAndCommonFilters(Builder $query, array $filters, array $periodBounds): void
    {
        if ($periodBounds['start'] !== null && $periodBounds['end'] !== null) {
            $query->whereBetween('created_at', [$periodBounds['start'], $periodBounds['end']]);
        }

        if (in_array($filters['status_sync'], ['success', 'failed', 'pending'], true)) {
            $query->where('status_sync', $filters['status_sync']);
        }

        if (in_array($filters['tipe_koneksi'], ['pppoe', 'static_ip'], true)) {
            $query->where('tipe_koneksi', $filters['tipe_koneksi']);
        }

        $search = $filters['q'];
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhere('no_tlp', 'like', "%{$search}%")
                    ->orWhere('hasil_poe_ip', 'like', "%{$search}%")
                    ->orWhere('mac_address', 'like', "%{$search}%")
                    ->orWhere('nama_teknisi', 'like', "%{$search}%");
            });
        }
    }

    /**
     * @param  Builder<PsbOrder>  $query
     */
    private function applySalesFilter(Builder $query, string $filterSales): void
    {
        if ($filterSales === 'tanpa_sales') {
            $query->whereNull('sales_id');
            return;
        }

        if ($filterSales === 'dengan_sales') {
            $query->whereNotNull('sales_id');
            return;
        }

        if ($filterSales !== '' && is_numeric($filterSales)) {
            $query->where('sales_id', (int) $filterSales);
        }
    }
}
