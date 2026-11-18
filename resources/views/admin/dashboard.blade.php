@extends('layouts.admin', ['title' => 'Log PSB & Kirim Ulang Konfigurasi'])

@section('content')
<div class="space-y-4 sm:space-y-5" x-data="{ activePhotoModal: null, activePhotoIndex: 0, photoRotation: 0 }">
    <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
            <div>
                <h1 class="text-[clamp(1.125rem,2vw,1.35rem)] font-extrabold text-[#06233B]">Log Pemasangan Baru (PSB) & Kirim Ulang NOC</h1>
                <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
                    Periksa antrean yang gagal terhubung ke MikroTik, verifikasi 5 foto dokumentasi privat secara berdampingan, lalu jalankan kirim ulang konfigurasi.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3.5 py-2 text-xs font-bold text-[#06233B] hover:bg-[#E0F2FE] active:bg-[#D0E6F8]">
                    <span>Total Order:</span>
                    <span class="font-mono text-sm font-extrabold">{{ $stats['total'] }}</span>
                </a>

                <a
                    href="{{ route('admin.dashboard', ['status_sync' => 'success']) }}"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-[1.5px] border-[#059669] bg-[#D1FAE5] px-3.5 py-2 text-xs font-bold text-[#065F46]">
                    <span>Sukses:</span>
                    <span class="font-mono text-sm font-extrabold">{{ $stats['success'] }}</span>
                </a>

                <a
                    href="{{ route('admin.dashboard', ['status_sync' => 'failed']) }}"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] px-3.5 py-2 text-xs font-bold text-[#991B1B]">
                    <span>Gagal (Perlu Retry):</span>
                    <span class="font-mono text-sm font-extrabold">{{ $stats['failed'] }}</span>
                </a>

                @if ($stats['pending'] > 0)
                <a
                    href="{{ route('admin.dashboard', ['status_sync' => 'pending']) }}"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border-[1.5px] border-[#D97706] bg-[#FEF3C7] px-3.5 py-2 text-xs font-bold text-[#78350F]">
                    <span>Pending:</span>
                    <span class="font-mono text-sm font-extrabold">{{ $stats['pending'] }}</span>
                </a>
                @endif

                <form method="POST" action="{{ route('admin.slots.sync') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-[#005F99] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E]">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Sync Slot Sekarang</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12 xl:items-start">
        <div class="space-y-4 xl:col-span-9">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-3.5 shadow-sm sm:p-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-12">
                    <div class="sm:col-span-2 2xl:col-span-4">
                        <label for="q" class="block text-xs font-bold uppercase text-[#06233B]">Cari Pelanggan / POE / IP / MAC / Teknisi</label>
                        <input
                            id="q"
                            name="q"
                            type="text"
                            value="{{ $filters['q'] }}"
                            placeholder="Ketik nama, IP, PPPoE, atau MAC..."
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>

                    <div class="2xl:col-span-2">
                        <label for="status_sync" class="block text-xs font-bold uppercase text-[#06233B]">Status Sync</label>
                        <select
                            id="status_sync"
                            name="status_sync"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                            <option value="">Semua Status</option>
                            <option value="failed" @selected($filters['status_sync']==='failed')>Failed (Gagal)</option>
                            <option value="success" @selected($filters['status_sync']==='success')>Success</option>
                            <option value="pending" @selected($filters['status_sync']==='pending')>Pending</option>
                        </select>
                    </div>

                    <div class="2xl:col-span-2">
                        <label for="tipe_koneksi" class="block text-xs font-bold uppercase text-[#06233B]">Tipe Koneksi</label>
                        <select
                            id="tipe_koneksi"
                            name="tipe_koneksi"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                            <option value="">Semua Tipe</option>
                            <option value="pppoe" @selected($filters['tipe_koneksi']==='pppoe')>PPPoE [Upgrade]</option>
                            <option value="static_ip" @selected($filters['tipe_koneksi']==='static_ip')>Static IP [Belum]</option>
                        </select>
                    </div>

                    <div class="2xl:col-span-2">
                        <label for="sales_id" class="block text-xs font-bold uppercase text-[#06233B]">Sales</label>
                        <select
                            id="sales_id"
                            name="sales_id"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                            <option value="">Semua Sales</option>
                            <option value="tanpa_sales" @selected($filters['sales_id']==='tanpa_sales')>Tanpa Sales</option>
                            @foreach ($salesList as $s)
                            <option value="{{ $s->id }}" @selected($filters['sales_id']===(string) $s->id)>
                                {{ $s->kode_sales_ip }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2 2xl:col-span-2">
                        <button
                            type="submit"
                            class="inline-flex min-h-[44px] flex-1 shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-[#005F99] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                            Saring Data
                        </button>
                        <a
                            href="{{ route('admin.dashboard') }}"
                            class="inline-flex min-h-[44px] shrink-0 items-center justify-center whitespace-nowrap rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#E0F2FE] active:bg-[#D0E6F8]">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#4A7396] bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#7FA6C9] text-left text-sm">
                        <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                            <tr>
                                <th scope="col" class="px-4 py-3.5">Status & Tindakan</th>
                                <th scope="col" class="px-4 py-3.5">Hasil POE / IP & Pesan Error</th>
                                <th scope="col" class="px-4 py-3.5">Pelanggan & Shareloc</th>
                                <th scope="col" class="px-4 py-3.5">ODP & Paket</th>
                                <th scope="col" class="px-4 py-3.5">MAC, Optik & 5 Foto</th>
                                <th scope="col" class="px-4 py-3.5">Sales & Teknisi</th>
                                <th scope="col" class="px-4 py-3.5">Waktu Input</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#7FA6C9] bg-white">
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
                                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-[#005F99] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E]">
                                            Retry Provisioning
                                        </button>
                                    </form>
                                    @endif
                                </td>

                                <td class="px-4 py-3.5">
                                    <div class="mb-1">
                                        @if ($order->tipe_koneksi === 'pppoe')
                                        <span class="rounded border border-[#0284C7] bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#075985]">
                                            [PPPoE]
                                        </span>
                                        @else
                                        <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-1.5 py-0.5 font-mono text-xs font-bold text-[#78350F]">
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
                                    <div class="mt-1.5 max-w-xs rounded-lg border border-[#DC2626] bg-[#FEE2E2] p-2 font-mono text-xs text-[#991B1B]">
                                        {{ $order->error_log }}
                                    </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3.5">
                                    <div class="font-extrabold text-[#06233B]">{{ $order->nama_pelanggan }}</div>
                                    <div class="font-mono text-xs font-semibold text-[#2C5270]">{{ $order->no_tlp }}</div>
                                    <div class="mt-1 max-w-xs text-xs text-[#2C5270]">{{ $order->alamat }}</div>
                                    @if (! empty($order->shareloc))
                                    <div class="mt-2">
                                        <a
                                            href="{{ $order->safeSharelocUrl() }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#0284C7] bg-[#E0F2FE] px-3 py-2 text-xs font-bold text-[#075985] hover:bg-[#005F99] hover:text-white active:bg-[#043A5E]">
                                            <span>Buka Shareloc Maps</span>
                                        </a>
                                    </div>
                                    @endif
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

                                    <div class="mt-2">
                                        <button
                                            type="button"
                                            @click="activePhotoIndex = 0; photoRotation = 0; activePhotoModal = @js($order->adminPhotoModalPayload())"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#005F99] hover:text-white active:bg-[#043A5E]">
                                            Lihat 5 Foto PSB
                                        </button>
                                    </div>
                                </td>

                                <td class="px-4 py-3.5 text-xs">
                                    <div class="font-bold text-[#06233B]">Sales: {{ $order->sales?->kode_sales_ip ?? 'TANPA SALES' }}</div>
                                    <div class="mt-0.5 text-[#2C5270]">Teknisi: <span class="font-bold text-[#06233B]">{{ $order->nama_teknisi }}</span></div>
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
                                        Belum ada catatan pemasangan PSB yang cocok dengan saringan ini.
                                    </div>
                                    <p class="mt-1 text-xs text-[#2C5270]">
                                        Ubah kriteria pencarian di atas atau tambahkan pemasangan baru melalui formulir teknisi.
                                    </p>
                                    <div class="mt-4">
                                        <a
                                            href="{{ route('psb.create') }}"
                                            class="inline-flex min-h-[44px] items-center rounded-xl bg-[#005F99] px-4 py-2 text-xs font-bold text-white hover:bg-[#054A78]">
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
                <div class="border-t border-[#7FA6C9] bg-[#EDF5FC] px-4 py-3">
                    {{ $orders->links() }}
                </div>
                @endif
            </div>
        </div>

        <aside class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm xl:col-span-3">
            <div class="flex items-center justify-between border-b border-[#7FA6C9] pb-3">
                <h2 class="text-sm font-extrabold text-[#06233B]">Slot IP Router Distribusi</h2>
                <a
                    href="{{ route('admin.slots.index') }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg border border-[#4A7396] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#005F99] hover:bg-[#E0F2FE]">
                    Semua Slot
                </a>
            </div>

            <div class="mt-3.5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
                @forelse ($distributionRouters as $router)
                @php
                    $avail = (int) $router->available_slots_count;
                    $used = (int) $router->used_slots_count;
                    $totalSlots = max(1, $avail + $used);
                    $usedPercent = min(100, (int) round(($used / $totalSlots) * 100));

                    if ($avail === 0) {
                        $badgeClasses = 'border-[#DC2626] bg-[#FEE2E2] text-[#991B1B]';
                        $barColor = 'bg-[#DC2626]';
                        $statusPrefix = 'HABIS · ';
                    } elseif ($avail <= 5) {
                        $badgeClasses = 'border-[#D97706] bg-[#FEF3C7] text-[#78350F]';
                        $barColor = 'bg-[#D97706]';
                        $statusPrefix = 'MENIPIS · ';
                    } else {
                        $badgeClasses = 'border-[#059669] bg-[#D1FAE5] text-[#065F46]';
                        $barColor = 'bg-[#059669]';
                        $statusPrefix = '';
                    }
                @endphp
                <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-xs font-extrabold text-[#06233B]">{{ $router->nama_router }}</div>
                            <div class="font-mono text-xs text-[#2C5270]">
                                {{ $router->ip_host }}
                                @if ($router->sales->isNotEmpty())
                                &middot; Sales: {{ $router->sales->pluck('kode_sales_ip')->join(', ') }}
                                @endif
                            </div>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded border px-2 py-0.5 font-mono text-xs font-bold {{ $badgeClasses }}">
                            {{ $statusPrefix }}{{ $avail }} Kosong
                        </span>
                    </div>

                    <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full border border-[#7FA6C9] bg-white" role="progressbar" aria-valuenow="{{ $usedPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Pemakaian Slot {{ $router->nama_router }}">
                        <div class="h-full {{ $barColor }}" style="width: {{ $usedPercent }}%"></div>
                    </div>

                    <div class="mt-2.5 flex items-center justify-between border-t border-[#7FA6C9] pt-2.5 text-xs text-[#2C5270]">
                        <span>Terpakai: <span class="font-mono font-bold text-[#06233B]">{{ $used }}</span> slot</span>
                        <form method="POST" action="{{ route('admin.slots.sync') }}">
                            @csrf
                            <input type="hidden" name="router_id" value="{{ $router->id }}">
                            <button
                                type="submit"
                                class="inline-flex min-h-[44px] items-center rounded-lg border border-[#4A7396] bg-white px-3 py-1.5 text-xs font-bold text-[#005F99] hover:bg-[#005F99] hover:text-white active:bg-[#043A5E]">
                                Sync Router
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] p-4 text-xs text-[#2C5270]">
                    Belum ada router distribusi terdaftar. Tambahkan router distribusi melalui menu Master Router.
                </div>
                @endforelse
            </div>
        </aside>
    </div>

    <x-noc.photo-inspector />
</div>
@endsection
