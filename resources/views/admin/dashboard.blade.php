@extends('layouts.admin', ['title' => 'Log PSB & Retry Provisioning'])

@section('content')
<div class="space-y-5">
    <!-- Compact Ocean Blue Operational Header Strip (Replaces bloated 4-card row) -->
    <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
            <div>
                <h1 class="text-lg font-extrabold text-[#06233B] sm:text-xl">Log Pemasangan Baru (PSB) & Retry NOC</h1>
                <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
                    Prioritas utama: pantau order berstatus <strong class="text-[#991B1B]">FAILED</strong> dan jalankan <strong>Retry Provisioning</strong> setelah kendala koneksi RouterOS teratasi.
                </p>
            </div>

            <!-- Inline Real Database Telemetry Pills + Manual Sync Action -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-3.5 py-2 text-xs font-bold text-[#06233B] hover:bg-[#E0F2FE]"
                >
                    <span>Total Order:</span>
                    <span class="font-mono text-sm font-extrabold">{{ $stats['total'] }}</span>
                </a>

                <a
                    href="{{ route('admin.dashboard', ['status_sync' => 'success']) }}"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-[1.5px] border-[#059669] bg-[#D1FAE5] px-3.5 py-2 text-xs font-bold text-[#065F46]"
                >
                    <span>Sukses:</span>
                    <span class="font-mono text-sm font-extrabold">{{ $stats['success'] }}</span>
                </a>

                <a
                    href="{{ route('admin.dashboard', ['status_sync' => 'failed']) }}"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] px-3.5 py-2 text-xs font-bold text-[#991B1B]"
                >
                    <span>Gagal (Perlu Retry):</span>
                    <span class="font-mono text-sm font-extrabold">{{ $stats['failed'] }}</span>
                </a>

                @if ($stats['pending'] > 0)
                    <a
                        href="{{ route('admin.dashboard', ['status_sync' => 'pending']) }}"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-[1.5px] border-[#D97706] bg-[#FEF3C7] px-3.5 py-2 text-xs font-bold text-[#78350F]"
                    >
                        <span>Pending:</span>
                        <span class="font-mono text-sm font-extrabold">{{ $stats['pending'] }}</span>
                    </a>
                @endif

                <form method="POST" action="{{ route('admin.slots.sync') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-[#005F99] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#054A78]"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Sync Slot Sekarang</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Desktop 12-Column Asymmetric Split: Left 9 Cols (Filter + Log Table), Right 3 Cols (Router Slot Monitor Rail) -->
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 xl:items-start">
        <!-- Primary Workspace: Filter + PSB Orders Table (9 Columns on Desktop) -->
        <div class="space-y-4 xl:col-span-9">
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('admin.dashboard') }}" class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <label for="q" class="block text-xs font-bold uppercase text-[#06233B]">Cari Pelanggan / POE / IP / MAC / Teknisi</label>
                        <input
                            id="q"
                            name="q"
                            type="text"
                            value="{{ $filters['q'] }}"
                            placeholder="Ketik nama, IP, PPPoE, atau MAC..."
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70"
                        >
                    </div>

                    <div class="lg:col-span-2">
                        <label for="status_sync" class="block text-xs font-bold uppercase text-[#06233B]">Status Sync</label>
                        <select
                            id="status_sync"
                            name="status_sync"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]"
                        >
                            <option value="">Semua Status</option>
                            <option value="failed" @selected($filters['status_sync'] === 'failed')>Failed (Gagal)</option>
                            <option value="success" @selected($filters['status_sync'] === 'success')>Success</option>
                            <option value="pending" @selected($filters['status_sync'] === 'pending')>Pending</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="tipe_koneksi" class="block text-xs font-bold uppercase text-[#06233B]">Tipe Koneksi</label>
                        <select
                            id="tipe_koneksi"
                            name="tipe_koneksi"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="pppoe" @selected($filters['tipe_koneksi'] === 'pppoe')>PPPoE [Upgrade]</option>
                            <option value="static_ip" @selected($filters['tipe_koneksi'] === 'static_ip')>Static IP [Belum]</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="sales_id" class="block text-xs font-bold uppercase text-[#06233B]">Sales</label>
                        <select
                            id="sales_id"
                            name="sales_id"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3 py-2 text-sm font-medium text-[#06233B]"
                        >
                            <option value="">Semua Sales</option>
                            @foreach ($salesList as $s)
                                <option value="{{ $s->id }}" @selected($filters['sales_id'] === (string) $s->id)>
                                    {{ $s->kode_sales_ip }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2 lg:col-span-2">
                        <button
                            type="submit"
                            class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-lg bg-[#005F99] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#054A78]"
                        >
                            Terapkan
                        </button>
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#E0F2FE]"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- PSB Orders Table -->
            <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#5B82A6] bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#B8D4EC] text-left text-sm">
                        <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                            <tr>
                                <th class="px-4 py-3.5">Status & Tindakan</th>
                                <th class="px-4 py-3.5">Hasil POE / IP & Log Error</th>
                                <th class="px-4 py-3.5">Pelanggan</th>
                                <th class="px-4 py-3.5">ODP & Paket</th>
                                <th class="px-4 py-3.5">MAC & Redaman Optik</th>
                                <th class="px-4 py-3.5">Sales & Teknisi</th>
                                <th class="px-4 py-3.5">Waktu Input</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#B8D4EC] bg-white">
                            @forelse ($orders as $order)
                                <tr class="align-top transition hover:bg-[#EDF5FC]/70">
                                    <td class="px-4 py-3.5">
                                        @if ($order->status_sync === 'success')
                                            <span class="inline-flex items-center rounded-md border border-[#059669] bg-[#D1FAE5] px-2.5 py-0.5 font-mono text-xs font-bold text-[#065F46]">
                                                SUCCESS
                                            </span>
                                        @elseif ($order->status_sync === 'failed')
                                            <span class="inline-flex items-center rounded-md border border-[#DC2626] bg-[#FEE2E2] px-2.5 py-0.5 font-mono text-xs font-bold text-[#991B1B]">
                                                FAILED
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md border border-[#D97706] bg-[#FEF3C7] px-2.5 py-0.5 font-mono text-xs font-bold text-[#78350F]">
                                                PENDING
                                            </span>
                                        @endif

                                        @if ($order->status_sync !== 'success')
                                            <form method="POST" action="{{ route('admin.orders.retry', $order) }}" class="mt-2">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-[#005F99] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#054A78]"
                                                >
                                                    Retry Provisioning
                                                </button>
                                            </form>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="mb-1">
                                            @if ($order->tipe_koneksi === 'pppoe')
                                                <span class="rounded border border-[#0284C7] bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-[11px] font-bold text-[#075985]">
                                                    [PPPoE]
                                                </span>
                                            @else
                                                <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-1.5 py-0.5 font-mono text-[11px] font-bold text-[#78350F]">
                                                    [Static IP]
                                                </span>
                                            @endif
                                        </div>

                                        @if ($order->hasil_poe_ip)
                                            <div class="font-mono text-xs font-extrabold text-[#065F46]">{{ $order->hasil_poe_ip }}</div>
                                        @else
                                            <div class="text-xs font-extrabold text-[#991B1B]">GAGAL AUTO-CONFIG (CEK NOC)</div>
                                        @endif

                                        @if ($order->error_log)
                                            <div class="mt-1.5 max-w-xs rounded-lg border border-[#DC2626] bg-[#FEE2E2] p-2 font-mono text-[11px] text-[#991B1B]">
                                                {{ $order->error_log }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="font-extrabold text-[#06233B]">{{ $order->nama_pelanggan }}</div>
                                        <div class="font-mono text-xs text-[#2C5270]">{{ $order->no_tlp }}</div>
                                        <div class="mt-1 max-w-xs text-xs text-[#2C5270]">{{ $order->alamat }}</div>
                                    </td>

                                    <td class="px-4 py-3.5 text-xs">
                                        <div class="font-bold text-[#06233B]">
                                            ODP: {{ $order->odp?->nama_odp ?? '-' }} (Port {{ $order->port }})
                                        </div>
                                        <div class="mt-1 font-medium text-[#2C5270]">
                                            Paket: <span class="font-bold text-[#005F99]">{{ $order->paket?->nama_paket ?? '-' }}</span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5 text-xs">
                                        <div class="font-mono font-bold text-[#06233B]">{{ $order->mac_address }}</div>
                                        <div class="text-[#2C5270]">{{ $order->brand_router }} &middot; Kabel: {{ $order->jarak_kabel }}</div>
                                        <div class="mt-0.5 font-mono text-[#06233B]">
                                            ODP: {{ $order->signal_odp }} | ONT: {{ $order->signal_ont }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5 text-xs">
                                        <div class="font-bold text-[#06233B]">Sales: {{ $order->sales?->kode_sales_ip ?? '-' }}</div>
                                        <div class="mt-0.5 text-[#2C5270]">Teknisi: <strong class="text-[#06233B]">{{ $order->nama_teknisi }}</strong></div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3.5 font-mono text-xs text-[#2C5270]">
                                        <div class="font-bold text-[#06233B]">{{ $order->created_at?->format('d/m/Y') }}</div>
                                        <div>{{ $order->created_at?->format('H:i:s') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-sm font-bold text-[#06233B]">
                                            Belum ada catatan instalasi PSB yang sesuai dengan kriteria pencarian ini.
                                        </div>
                                        <p class="mt-1 text-xs text-[#2C5270]">
                                            Mulai tambahkan instalasi pelanggan baru melalui formulir teknisi atau ubah filter pencarian di atas.
                                        </p>
                                        <div class="mt-4">
                                            <a
                                                href="{{ route('psb.create') }}"
                                                class="inline-flex min-h-[44px] items-center rounded-xl bg-[#005F99] px-4 py-2 text-xs font-bold text-white hover:bg-[#054A78]"
                                            >
                                                Buka Form Input PSB Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="border-t border-[#B8D4EC] bg-[#EDF5FC] px-4 py-3">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Operational Rail: Router IP Slot Capacity Monitor (3 Columns on Desktop, 2-col Grid on Tablet) -->
        <aside class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm xl:col-span-3">
            <div class="flex items-center justify-between border-b border-[#B8D4EC] pb-3">
                <div>
                    <div class="font-mono text-[11px] font-bold uppercase text-[#005F99]">Monitor Kapasitas</div>
                    <h2 class="text-sm font-extrabold text-[#06233B]">Slot IP Router Distribusi</h2>
                </div>
                <a
                    href="{{ route('admin.slots.index') }}"
                    class="inline-flex min-h-[36px] items-center rounded-lg border border-[#5B82A6] bg-[#EDF5FC] px-2.5 py-1 text-xs font-bold text-[#005F99] hover:bg-[#E0F2FE]"
                >
                    Semua Slot
                </a>
            </div>

            <div class="mt-3.5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
                @forelse ($distributionRouters as $router)
                    <div class="rounded-xl border border-[#B8D4EC] bg-[#EDF5FC] p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-xs font-extrabold text-[#06233B]">{{ $router->nama_router }}</div>
                                <div class="font-mono text-[11px] text-[#2C5270]">
                                    {{ $router->ip_host }}
                                    @if ($router->sales->isNotEmpty())
                                        &middot; Sales: <strong>{{ $router->sales->pluck('kode_sales_ip')->join(', ') }}</strong>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded border border-[#059669] bg-[#D1FAE5] px-2 py-0.5 font-mono text-xs font-bold text-[#065F46]">
                                {{ $router->available_slots_count }} Kosong
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between border-t border-[#B8D4EC] pt-2 text-[11px] text-[#2C5270]">
                            <span>Terpakai: <strong class="font-mono text-[#06233B]">{{ $router->used_slots_count }}</strong> slot</span>
                            <form method="POST" action="{{ route('admin.slots.sync') }}">
                                @csrf
                                <input type="hidden" name="router_id" value="{{ $router->id }}">
                                <button
                                    type="submit"
                                    class="font-bold text-[#005F99] underline hover:text-[#054A78]"
                                >
                                    Sync Router
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-[#B8D4EC] bg-[#EDF5FC] p-4 text-xs text-[#2C5270]">
                        Belum ada router distribusi terdaftar. Tambahkan router distribusi di menu <strong>Master Router</strong>.
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
@endsection
