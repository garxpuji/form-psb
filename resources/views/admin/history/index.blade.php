@extends('layouts.admin', ['title' => 'Riwayat & Rekapitulasi Pemasangan Baru (PSB)'])

@section('content')
@php
$daftarBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
@endphp

<div
    class="space-y-4 sm:space-y-5"
    x-data="{
        periode: @js($filters['periode']),
        activePhotoModal: null,
        activePhotoIndex: 0,
        photoRotation: 0
    }">
    <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-[clamp(1.125rem,2vw,1.35rem)] font-extrabold text-[#06233B]">
                        Riwayat & Rekapitulasi Pemasangan Baru (PSB)
                    </h1>
                    <span class="rounded-md border border-[#0284C7] bg-[#E0F2FE] px-2.5 py-0.5 font-mono text-xs font-bold text-[#075985]">
                        {{ $periodLabel }}
                    </span>
                </div>
                <p class="mt-1 text-xs font-medium text-[#2C5270]">
                    Saring riwayat pemasangan berdasarkan tanggal harian, rentang tanggal, mingguan, bulanan, atau tahunan serta kategori sales maupun tanpa sales.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.riwayat.index', array_merge(request()->except('page'), ['periode' => 'harian', 'tanggal_harian' => now()->toDateString()])) }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg border px-3.5 py-2 text-xs font-bold transition {{ $filters['periode'] === 'harian' ? 'border-[#005F99] bg-[#005F99] text-white' : 'border-[#4A7396] bg-[#EDF5FC] text-[#06233B] hover:bg-[#E0F2FE]' }}">
                    Hari Ini
                </a>
                <a
                    href="{{ route('admin.riwayat.index', array_merge(request()->except('page'), ['periode' => 'mingguan', 'minggu' => now()->format('o-\WW')])) }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg border px-3.5 py-2 text-xs font-bold transition {{ $filters['periode'] === 'mingguan' ? 'border-[#005F99] bg-[#005F99] text-white' : 'border-[#4A7396] bg-[#EDF5FC] text-[#06233B] hover:bg-[#E0F2FE]' }}">
                    Minggu Ini
                </a>
                <a
                    href="{{ route('admin.riwayat.index', array_merge(request()->except('page'), ['periode' => 'bulanan', 'bulan' => now()->month, 'tahun' => now()->year])) }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg border px-3.5 py-2 text-xs font-bold transition {{ $filters['periode'] === 'bulanan' ? 'border-[#005F99] bg-[#005F99] text-white' : 'border-[#4A7396] bg-[#EDF5FC] text-[#06233B] hover:bg-[#E0F2FE]' }}">
                    Bulan Ini
                </a>
                <a
                    href="{{ route('admin.riwayat.index', array_merge(request()->except('page'), ['periode' => 'tahunan', 'tahun' => now()->year])) }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg border px-3.5 py-2 text-xs font-bold transition {{ $filters['periode'] === 'tahunan' ? 'border-[#005F99] bg-[#005F99] text-white' : 'border-[#4A7396] bg-[#EDF5FC] text-[#06233B] hover:bg-[#E0F2FE]' }}">
                    Tahun Ini
                </a>
                <a
                    href="{{ route('admin.riwayat.export', request()->query()) }}"
                    class="inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-[#059669] bg-[#D1FAE5] px-3.5 py-2 text-xs font-extrabold text-[#065F46] hover:bg-[#A7F3D0]">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Unduh Rekap CSV</span>
                </a>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.riwayat.index') }}" class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-3.5 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-2">
                <label for="periode" class="block text-xs font-bold uppercase text-[#06233B]">Mode Periode</label>
                <select
                    id="periode"
                    name="periode"
                    x-model="periode"
                    class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-bold text-[#06233B]">
                    <option value="semua">Semua Waktu</option>
                    <option value="harian">Harian (1 Tanggal)</option>
                    <option value="tanggal">Rentang Tanggal</option>
                    <option value="mingguan">Mingguan (Per Minggu)</option>
                    <option value="bulanan">Bulanan (Per Bulan)</option>
                    <option value="tahunan">Tahunan (Per Tahun)</option>
                </select>
            </div>

            <div class="lg:col-span-4">
                <div x-show="periode === 'semua'" x-cloak>
                    <span class="block text-xs font-bold uppercase text-[#2C5270]">Cakupan Waktu</span>
                    <div class="mt-1 flex min-h-[44px] items-center rounded-lg border border-[#7FA6C9] bg-[#EDF5FC] px-3 text-xs font-bold text-[#06233B]">
                        Seluruh riwayat dari awal
                    </div>
                </div>

                <div x-show="periode === 'harian'" x-cloak>
                    <label for="tanggal_harian" class="block text-xs font-bold uppercase text-[#06233B]">Pilih Tanggal Harian</label>
                    <input
                        id="tanggal_harian"
                        name="tanggal_harian"
                        type="date"
                        value="{{ $filters['tanggal_harian'] }}"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>

                <div x-show="periode === 'tanggal'" x-cloak class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="tanggal_mulai" class="block text-xs font-bold uppercase text-[#06233B]">Dari Tanggal</label>
                        <input
                            id="tanggal_mulai"
                            name="tanggal_mulai"
                            type="date"
                            value="{{ $filters['tanggal_mulai'] }}"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-2.5 py-2 font-mono text-xs font-bold text-[#06233B]">
                    </div>
                    <div>
                        <label for="tanggal_selesai" class="block text-xs font-bold uppercase text-[#06233B]">Sampai Tanggal</label>
                        <input
                            id="tanggal_selesai"
                            name="tanggal_selesai"
                            type="date"
                            value="{{ $filters['tanggal_selesai'] }}"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-2.5 py-2 font-mono text-xs font-bold text-[#06233B]">
                    </div>
                </div>

                <div x-show="periode === 'mingguan'" x-cloak>
                    <label for="minggu" class="block text-xs font-bold uppercase text-[#06233B]">Pilih Minggu</label>
                    <input
                        id="minggu"
                        name="minggu"
                        type="week"
                        value="{{ $filters['minggu'] }}"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>

                <div x-show="periode === 'bulanan'" x-cloak class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="bulan" class="block text-xs font-bold uppercase text-[#06233B]">Bulan</label>
                        <select
                            id="bulan"
                            name="bulan"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-2.5 py-2 text-sm font-bold text-[#06233B]">
                            @foreach ($daftarBulan as $num => $namaBln)
                            <option value="{{ $num }}" @selected((int) $filters['bulan']===$num)>{{ $namaBln }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tahun_bulanan" class="block text-xs font-bold uppercase text-[#06233B]">Tahun</label>
                        <input
                            id="tahun_bulanan"
                            name="tahun"
                            type="number"
                            min="2020"
                            max="2100"
                            :disabled="periode !== 'bulanan'"
                            value="{{ $filters['tahun'] }}"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                </div>

                <div x-show="periode === 'tahunan'" x-cloak>
                    <label for="tahun_tahunan" class="block text-xs font-bold uppercase text-[#06233B]">Pilih Tahun</label>
                    <input
                        id="tahun_tahunan"
                        name="tahun"
                        type="number"
                        min="2020"
                        max="2100"
                        :disabled="periode !== 'tahunan'"
                        value="{{ $filters['tahun'] }}"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>
            </div>

            <div class="lg:col-span-3">
                <label for="filter_sales" class="block text-xs font-bold uppercase text-[#06233B]">Filter Sales / Tanpa Sales</label>
                <select
                    id="filter_sales"
                    name="filter_sales"
                    class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-bold text-[#06233B]">
                    <option value="" @selected($filters['filter_sales']==='')>Semua (Sales & Tanpa Sales)</option>
                    <option value="dengan_sales" @selected($filters['filter_sales']==='dengan_sales')>Semua Dengan Sales</option>
                    <option value="tanpa_sales" @selected($filters['filter_sales']==='tanpa_sales')>Khusus Tanpa Sales (Kantor)</option>
                    <optgroup label="Pilih Per Kode Sales">
                        @foreach ($salesList as $s)
                        <option value="{{ $s->id }}" @selected($filters['filter_sales']===(string) $s->id)>
                            Sales {{ $s->kode_sales_ip }} - {{ $s->nama_sales }}
                        </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <div class="lg:col-span-3">
                <label for="q" class="block text-xs font-bold uppercase text-[#06233B]">Cari Nama / Alamat / Teknisi</label>
                <div class="mt-1 flex gap-2">
                    <input
                        id="q"
                        name="q"
                        type="text"
                        value="{{ $filters['q'] }}"
                        placeholder="Kata kunci..."
                        class="block min-h-[44px] min-w-0 flex-1 rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    <button
                        type="submit"
                        class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-lg bg-[#005F99] px-4 py-2 text-xs font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                        Saring
                    </button>
                    <a
                        href="{{ route('admin.riwayat.index') }}"
                        class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                        Reset
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12 xl:items-start">
        <div class="space-y-4 sm:space-y-5 xl:col-span-4">
            <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm">
                <div class="border-b border-[#7FA6C9] pb-2.5">
                    <h2 class="text-sm font-extrabold text-[#06233B]">Total Pemasangan ({{ $periodLabel }})</h2>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2.5">
                    <div class="rounded-xl border border-[#0284C7] bg-[#E0F2FE] p-3">
                        <div class="text-xs font-bold uppercase text-[#075985]">Total PSB</div>
                        <div class="mt-0.5 font-mono text-xl font-extrabold text-[#06233B]">{{ $summary['total'] }}</div>
                    </div>
                    <div class="rounded-xl border border-[#059669] bg-[#D1FAE5] p-3">
                        <div class="text-xs font-bold uppercase text-[#065F46]">Sales</div>
                        <div class="mt-0.5 font-mono text-xl font-extrabold text-[#065F46]">{{ $summary['dengan_sales'] }}</div>
                    </div>
                    <div class="rounded-xl border border-[#D97706] bg-[#FEF3C7] p-3">
                        <div class="text-xs font-bold uppercase text-[#78350F]">Non-Sales</div>
                        <div class="mt-0.5 font-mono text-xl font-extrabold text-[#78350F]">{{ $summary['tanpa_sales'] }}</div>
                    </div>
                </div>

                <div class="mt-2.5 grid grid-cols-2 gap-2.5 text-xs">
                    <div class="flex items-center justify-between rounded-lg border border-[#7FA6C9] bg-[#EDF5FC] px-3 py-2">
                        <span class="font-bold text-[#2C5270]">PPPoE [Upgrade]:</span>
                        <span class="font-mono font-extrabold text-[#06233B]">{{ $summary['pppoe'] }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-[#7FA6C9] bg-[#EDF5FC] px-3 py-2">
                        <span class="font-bold text-[#2C5270]">Static IP [Belum]:</span>
                        <span class="font-mono font-extrabold text-[#06233B]">{{ $summary['static_ip'] }}</span>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#4A7396] bg-white shadow-sm">
                <div class="border-b border-[#7FA6C9] bg-[#072A47] px-4 py-3 text-white">
                    <h2 class="text-sm font-extrabold">Rekapitulasi Per Sales & Tanpa Sales</h2>
                    <p class="text-xs text-[#E0F2FE]">Pilih baris sales untuk menyaring daftar riwayat di sebelah kanan.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#7FA6C9] text-left text-xs">
                        <thead class="bg-[#EDF5FC] font-bold uppercase text-[#06233B]">
                            <tr>
                                <th scope="col" class="px-3.5 py-2.5">Kategori / Sales</th>
                                <th scope="col" class="px-3 py-2.5 text-center">PPPoE</th>
                                <th scope="col" class="px-3 py-2.5 text-center">Static IP</th>
                                <th scope="col" class="px-3.5 py-2.5 text-right">Total PSB</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#7FA6C9] bg-white">
                            @foreach ($rekapRows as $row)
                            @php
                            $isSelected = $filters['filter_sales'] === $row['filter_key'];
                            @endphp
                            <tr class="{{ $isSelected ? 'bg-[#E0F2FE]' : 'hover:bg-[#EDF5FC]/70' }}">
                                <td class="px-3.5 py-2">
                                    <a
                                        href="{{ route('admin.riwayat.index', array_merge(request()->except('page'), ['filter_sales' => $row['filter_key']])) }}"
                                        class="group flex min-h-[44px] items-center gap-1.5">
                                        @if ($row['is_tanpa_sales'])
                                        <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-2 py-0.5 font-mono text-xs font-extrabold text-[#78350F]">
                                            TANPA SALES
                                        </span>
                                        @else
                                        <span class="rounded border border-[#0284C7] bg-[#E0F2FE] px-2 py-0.5 font-mono text-xs font-extrabold text-[#075985]">
                                            {{ $row['kode'] }}
                                        </span>
                                        @endif
                                        <span class="font-bold text-[#06233B] group-hover:text-[#005F99] group-hover:underline">
                                            {{ $row['label'] }}
                                        </span>
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-center font-mono font-bold text-[#075985]">{{ $row['pppoe'] }}</td>
                                <td class="px-3 py-2 text-center font-mono font-bold text-[#78350F]">{{ $row['static_ip'] }}</td>
                                <td class="px-3.5 py-2 text-right font-mono text-sm font-extrabold text-[#06233B]">{{ $row['total'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#4A7396] bg-white shadow-sm xl:col-span-8">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#7FA6C9] bg-[#EDF5FC] px-4 py-3">
                <h2 class="text-sm font-extrabold text-[#06233B]">
                    Daftar Riwayat Pemasangan ({{ $orders->total() }} Data)
                </h2>
                @if ($filters['filter_sales'] !== '')
                <a
                    href="{{ route('admin.riwayat.index', array_merge(request()->except(['page', 'filter_sales']))) }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg border border-[#005F99] bg-white px-3 py-2 text-xs font-bold text-[#005F99] hover:bg-[#E0F2FE]">
                    Tampilkan Semua Sales
                </a>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#7FA6C9] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Tanggal & Waktu</th>
                            <th scope="col" class="px-4 py-3.5">Sales</th>
                            <th scope="col" class="px-4 py-3.5">Pelanggan & Shareloc</th>
                            <th scope="col" class="px-4 py-3.5">ODP & Paket</th>
                            <th scope="col" class="px-4 py-3.5">POE / IP & Teknisi</th>
                            <th scope="col" class="px-4 py-3.5">Dokumentasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#7FA6C9] bg-white">
                        @forelse ($orders as $order)
                        <tr class="align-top hover:bg-[#EDF5FC]/70">
                            <td class="whitespace-nowrap px-4 py-3.5 font-mono text-xs">
                                <div class="font-extrabold text-[#06233B]">{{ $order->created_at?->format('d/m/Y') }}</div>
                                <div class="text-[#2C5270]">{{ $order->created_at?->format('H:i:s') }}</div>
                            </td>

                            <td class="px-4 py-3.5">
                                @if ($order->sales)
                                <span class="inline-flex rounded-md border border-[#0284C7] bg-[#E0F2FE] px-2.5 py-0.5 font-mono text-xs font-extrabold text-[#075985]">
                                    {{ $order->sales->kode_sales_ip }}
                                </span>
                                <div class="mt-0.5 text-xs font-medium text-[#2C5270]">{{ $order->sales->nama_sales }}</div>
                                @else
                                <span class="inline-flex rounded-md border border-[#D97706] bg-[#FEF3C7] px-2.5 py-0.5 font-mono text-xs font-extrabold text-[#78350F]">
                                    TANPA SALES
                                </span>
                                <div class="mt-0.5 text-xs font-medium text-[#2C5270]">Langsung / Kantor</div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="font-extrabold text-[#06233B]">{{ $order->nama_pelanggan }}</div>
                                <div class="font-mono text-xs font-semibold text-[#2C5270]">{{ $order->no_tlp }}</div>
                                <div class="mt-0.5 max-w-xs text-xs text-[#2C5270]">{{ $order->alamat }}</div>
                                @if (! empty($order->shareloc))
                                <div class="mt-2">
                                    <a
                                        href="{{ $order->safeSharelocUrl() }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex min-h-[44px] items-center rounded-lg border border-[#0284C7] bg-[#E0F2FE] px-3 py-2 text-xs font-bold text-[#075985] hover:bg-[#005F99] hover:text-white active:bg-[#043A5E]">
                                        Buka Shareloc Maps
                                    </a>
                                </div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 text-xs">
                                <div class="font-bold text-[#06233B]">
                                    {{ $order->odp?->nama_odp ?? '-' }} (Port {{ $order->port }})
                                </div>
                                <div class="mt-1">
                                    @if ($order->tipe_koneksi === 'pppoe')
                                    <span class="rounded border border-[#0284C7] bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#075985]">
                                        [PPPoE] {{ $order->paket?->nama_paket }}
                                    </span>
                                    @else
                                    <span class="rounded border border-[#D97706] bg-[#FEF3C7] px-1.5 py-0.5 font-mono text-xs font-bold text-[#78350F]">
                                        [Static IP] {{ $order->paket?->nama_paket }}
                                    </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3.5 text-xs">
                                @if ($order->hasil_poe_ip)
                                <div class="font-mono font-extrabold text-[#065F46]">{{ $order->hasil_poe_ip }}</div>
                                @else
                                <div class="font-bold text-[#991B1B]">GAGAL AUTO-CONFIG</div>
                                @endif
                                <div class="mt-0.5 font-mono text-xs text-[#2C5270]">MAC: {{ $order->mac_address }}</div>
                                <div class="mt-0.5 text-[#2C5270]">Teknisi: <span class="font-bold text-[#06233B]">{{ $order->nama_teknisi }}</span></div>
                            </td>

                            <td class="px-4 py-3.5 text-xs">
                                <button
                                    type="button"
                                    @click="activePhotoIndex = 0; photoRotation = 0; activePhotoModal = @js($order->adminPhotoModalPayload())"
                                    class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 text-xs font-bold text-[#06233B] hover:bg-[#005F99] hover:text-white active:bg-[#043A5E]">
                                    Lihat 5 Foto PSB
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-sm font-bold text-[#06233B]">
                                    Belum ada data pemasangan PSB pada periode atau kategori sales ini.
                                </div>
                                <p class="mt-1 text-xs text-[#2C5270]">
                                    Pilih rentang periode lain (Harian, Mingguan, Bulanan, Tahunan, atau Semua Waktu) di bagian atas.
                                </p>
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

    <x-noc.photo-inspector />
</div>
@endsection
