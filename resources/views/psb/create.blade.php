@extends('layouts.app', ['title' => 'Formulir Pasang Baru (PSB) - ISP Auto-PSB'])

@section('content')
@php
$psbResult = session('psb_result');
$odpMap = $odps->mapWithKeys(fn ($o) => [
$o->id => [
'nama_odp' => $o->nama_odp,
'kode_odp' => $o->kode_odp,
'status_upgrade' => $o->status_upgrade,
],
]);
$paketMap = $pakets->mapWithKeys(fn ($p) => [
$p->id => $p->nama_paket,
]);
$salesMap = $salesList->mapWithKeys(fn ($s) => [
$s->id => [
'kode_ip' => $s->kode_sales_ip,
'kode_pppoe' => $s->kode_sales_pppoe,
'router' => $s->ipRouter?->nama_router ?? '-',
],
]);
@endphp

<div
    class="min-h-screen pb-14"
    x-data="psbFormApp({
        userName: @js($user->name),
        isNonTeam: @js($isNonTeam),
        odpMap: @js($odpMap),
        paketMap: @js($paketMap),
        salesMap: @js($salesMap),
        initialNama: @js((string) old('nama_pelanggan', '')),
        initialAlamat: @js((string) old('alamat', '')),
        initialShareloc: @js((string) old('shareloc', '')),
        initialNoTlp: @js((string) old('no_tlp', '')),
        initialOdpId: @js((string) old('odp_id', '')),
        initialPort: @js((string) old('port', '')),
        initialPaketId: @js((string) old('paket_id', '')),
        initialSalesId: @js((string) old('sales_id', '')),
        initialMac: @js((string) old('mac_address', '')),
        initialBrand: @js((string) old('brand_router', '')),
        initialJarak: @js((string) old('jarak_kabel', '')),
        initialSignalOdp: @js((string) old('signal_odp', '')),
        initialSignalOnt: @js((string) old('signal_ont', '')),
        initialRekan: @js((string) old('rekan_teknisi', '')),
        hasResultModal: @js($psbResult !== null),
        resultCopyValue: @js((string) ($psbResult['raw_poe_ip'] ?? $psbResult['hasil_poe_ip'] ?? '')),
    })">
    <header class="border-b border-[#124670] bg-[#072A47] text-white">
        <div class="mx-auto flex w-full max-w-[1600px] flex-wrap items-center justify-between gap-3 px-3.5 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#7DD3FC] bg-[#005F99] font-mono text-xs font-extrabold text-white">
                    PSB
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-base font-extrabold text-white sm:text-lg">{{ $user->name }}</h1>
                        <span class="rounded border border-[#7DD3FC] bg-[#0B3B63] px-2 py-0.5 font-mono text-[11px] font-bold text-[#BAE6FD]">
                            {{ strtoupper($user->role) }}
                        </span>
                    </div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-[#BAE6FD]">
                        <span>Tujuan laporan Telegram:</span>
                        @if (! $isNonTeam && $targetGroup)
                        <span class="inline-flex items-center rounded bg-[#D1FAE5] px-2 py-0.5 font-mono text-[11px] font-bold text-[#065F46]">
                            {{ $targetGroup->nama_grup }}
                        </span>
                        @else
                        <span class="inline-flex items-center rounded bg-[#FEF3C7] px-2 py-0.5 font-mono text-[11px] font-bold text-[#78350F]">
                            {{ $targetGroup?->nama_grup ?? 'ALL TEAM PSB' }} [NON-TIM PSB]
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if ($user->isAdmin())
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC] bg-[#005F99] px-3.5 py-2 text-xs font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E]">
                    Panel Admin NOC
                </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC]/70 bg-[#0B3B63] px-3.5 py-2 text-xs font-bold text-white transition hover:bg-[#991B1B] active:bg-[#7F1D1D]">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1600px] px-3.5 pt-4 sm:px-6 sm:pt-5 lg:px-8">
        @if ($errors->any())
        <div class="mb-4 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] p-3.5 text-xs text-[#991B1B]" role="alert">
            <div class="font-bold">Periksa kembali isian formulir PSB berikut:</div>
            <ul class="mt-1.5 list-inside list-disc space-y-1 font-medium">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
            <form
                method="POST"
                action="{{ route('psb.store') }}"
                enctype="multipart/form-data"
                @submit="handleFormSubmit($event)"
                class="space-y-4 sm:space-y-5 lg:col-span-8"
                novalidate>
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:gap-5 md:grid-cols-2">
                    <section class="flex flex-col justify-between rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
                        <div class="space-y-4">
                            <div class="border-b border-[#B8D4EC] pb-2.5">
                                <h2 class="text-[clamp(1rem,1.6vw,1.125rem)] font-extrabold text-[#06233B]">Data Pelanggan, Alamat & Shareloc</h2>
                            </div>

                            <div>
                                <label for="nama_pelanggan" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Nama Pelanggan <span class="text-[#991B1B]">*</span>
                                </label>
                                <input
                                    id="nama_pelanggan"
                                    name="nama_pelanggan"
                                    type="text"
                                    x-model="namaPelanggan"
                                    required
                                    placeholder="Contoh: ASTONI / NURHAIDA"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            </div>

                            <div>
                                <label for="no_tlp" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    No. Telepon / WhatsApp <span class="text-[#991B1B]">*</span>
                                </label>
                                <input
                                    id="no_tlp"
                                    name="no_tlp"
                                    type="tel"
                                    inputmode="tel"
                                    x-model="noTlp"
                                    required
                                    placeholder="Contoh: 081234567890"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            </div>

                            <div>
                                <label for="alamat" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Alamat Pemasangan <span class="text-[#991B1B]">*</span>
                                </label>
                                <textarea
                                    id="alamat"
                                    name="alamat"
                                    rows="2"
                                    x-model="alamat"
                                    required
                                    placeholder="Tulis alamat rumah atau lokasi pemasangan"
                                    class="mt-1.5 block w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70"></textarea>
                            </div>

                            <div class="rounded-xl border border-[#5B82A6] bg-[#EDF5FC] p-3.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label for="shareloc" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                        Link Shareloc / Koordinat Lokasi <span class="text-[#991B1B]">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="fetchCurrentGpsLocation()"
                                        :disabled="fetchingGps"
                                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#005F99] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E] disabled:opacity-60">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span x-text="fetchingGps ? 'Mengambil GPS...' : 'Ambil Shareloc GPS Saat Ini'">Ambil Shareloc GPS Saat Ini</span>
                                    </button>
                                </div>
                                <input
                                    id="shareloc"
                                    name="shareloc"
                                    type="text"
                                    x-model="shareloc"
                                    required
                                    placeholder="https://www.google.com/maps?q=-6.200,106.816 atau tempel link Maps"
                                    class="mt-2 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2 font-mono text-xs font-bold text-[#06233B] placeholder-[#2C5270]/70">
                                <p x-show="gpsStatus !== ''" x-cloak class="mt-1.5 text-xs font-semibold" :class="gpsError ? 'text-[#991B1B]' : 'text-[#065F46]'" x-text="gpsStatus"></p>
                            </div>

                            <div>
                                <label for="sales_id" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Kode Sales (Pilih Sales atau Tanpa Sales)
                                </label>
                                <select
                                    id="sales_id"
                                    name="sales_id"
                                    x-model="selectedSalesId"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B]">
                                    <option value="">-- Tanpa Sales (Langsung / Kantor) --</option>
                                    @foreach ($salesList as $s)
                                    <option value="{{ $s->id }}">
                                        {{ $s->kode_sales_ip }} - {{ $s->nama_sales }} (PPPoE: {{ $s->kode_sales_pppoe }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-[#B8D4EC] pt-4">
                            <label for="rekan_teknisi" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                Rekan Teknisi (Opsional)
                            </label>
                            <input
                                id="rekan_teknisi"
                                name="rekan_teknisi"
                                type="text"
                                x-model="rekanTeknisi"
                                placeholder="Kosongkan jika sendiri, atau ketik nama rekan (contoh: nopal)"
                                class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            <p class="mt-1.5 text-xs font-medium text-[#2C5270]">
                                Nama teknisi di laporan: <span class="font-mono font-bold text-[#06233B]" x-text="formattedTechnicianPreview()"></span>
                            </p>
                        </div>
                    </section>

                    <section class="space-y-4 rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
                        <div class="border-b border-[#B8D4EC] pb-2.5">
                            <h2 class="text-[clamp(1rem,1.6vw,1.125rem)] font-extrabold text-[#06233B]">ODP, Paket & Parameter Optik ONT</h2>
                        </div>

                        <div>
                            <label for="odp_id" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                Pilih ODP (Ketik untuk Cari) <span class="text-[#991B1B]">*</span>
                            </label>
                            <div class="mt-1.5">
                                <select
                                    id="odp_id"
                                    name="odp_id"
                                    required
                                    x-ref="odpSelect"
                                    @change="selectedOdpId = $event.target.value"
                                    placeholder="Ketik nama ODP (misal: U15 B)...">
                                    <option value="">-- Pilih ODP --</option>
                                    @foreach ($odps as $odp)
                                    <option
                                        value="{{ $odp->id }}"
                                        data-status="{{ $odp->status_upgrade }}"
                                        data-kode="{{ $odp->kode_odp }}"
                                        @selected((string) old('odp_id')===(string) $odp->id)
                                        >
                                        {{ $odp->nama_odp }} {{ $odp->status_upgrade === 'upgrade' ? '[PPPoE]' : '[IP]' }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <template x-if="currentOdp()">
                                <div class="mt-2 flex items-center justify-between rounded-lg border border-[#5B82A6] bg-[#EDF5FC] px-3 py-2 text-xs">
                                    <span class="font-semibold text-[#2C5270]">Mode konfigurasi:</span>
                                    <span
                                        x-show="currentOdp().status_upgrade === 'upgrade'"
                                        class="inline-flex items-center rounded border border-[#0284C7] bg-[#E0F2FE] px-2 py-0.5 font-mono font-bold text-[#075985]">
                                        [PPPoE] Buat Secret Baru
                                    </span>
                                    <span
                                        x-show="currentOdp().status_upgrade !== 'upgrade'"
                                        class="inline-flex items-center rounded border border-[#D97706] bg-[#FEF3C7] px-2 py-0.5 font-mono font-bold text-[#78350F]">
                                        [IP] Simple Queue + Static ARP
                                    </span>
                                </div>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <div>
                                <label for="port" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Port ODP (1-32) <span class="text-[#991B1B]">*</span>
                                </label>
                                <input
                                    id="port"
                                    name="port"
                                    type="number"
                                    inputmode="numeric"
                                    min="1"
                                    max="32"
                                    x-model="port"
                                    required
                                    placeholder="1 s/d 32"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            </div>

                            <div>
                                <label for="paket_id" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Paket Internet <span class="text-[#991B1B]">*</span>
                                </label>
                                <select
                                    id="paket_id"
                                    name="paket_id"
                                    x-model="selectedPaketId"
                                    required
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B]">
                                    <option value="">-- Pilih Paket --</option>
                                    @foreach ($pakets as $paket)
                                    <option value="{{ $paket->id }}">
                                        {{ $paket->nama_paket }} ({{ $paket->queue_max_limit }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="mac_address" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    MAC Address ONT / Router <span class="text-[#991B1B]">*</span>
                                </label>
                                <span
                                    class="rounded px-1.5 py-0.5 font-mono text-xs font-bold"
                                    :class="macHexCount() === 12 ? 'bg-[#D1FAE5] text-[#065F46]' : 'bg-[#EDF5FC] text-[#2C5270]'"
                                    x-text="macHexCount() + '/12 HEX'"></span>
                            </div>
                            <input
                                id="mac_address"
                                name="mac_address"
                                type="text"
                                x-model="macInput"
                                @input="formatMacInput()"
                                maxlength="17"
                                required
                                placeholder="F8:14:FE:87:6D:0A atau f814fe876d0a"
                                class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 font-mono text-sm font-bold uppercase text-[#06233B] placeholder-[#2C5270]/70">
                            <p x-show="macError !== ''" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="macError"></p>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <div>
                                <label for="brand_router" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Brand Router / ONT <span class="text-[#991B1B]">*</span>
                                </label>
                                <input
                                    id="brand_router"
                                    name="brand_router"
                                    type="text"
                                    x-model="brandRouter"
                                    required
                                    placeholder="ZTE F609 / Huawei"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            </div>

                            <div>
                                <label for="jarak_kabel" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Jarak Kabel <span class="text-[#991B1B]">*</span>
                                </label>
                                <input
                                    id="jarak_kabel"
                                    name="jarak_kabel"
                                    type="text"
                                    x-model="jarakKabel"
                                    required
                                    placeholder="Contoh: 120m"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <div>
                                <label for="signal_odp" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Signal ODP (dBm) <span class="text-[#991B1B]">*</span>
                                </label>
                                <input
                                    id="signal_odp"
                                    name="signal_odp"
                                    type="text"
                                    x-model="signalOdp"
                                    required
                                    placeholder="Contoh: -17.4 dBm"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            </div>

                            <div>
                                <label for="signal_ont" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Signal ONT (dBm) <span class="text-[#991B1B]">*</span>
                                </label>
                                <input
                                    id="signal_ont"
                                    name="signal_ont"
                                    type="text"
                                    x-model="signalOnt"
                                    required
                                    placeholder="Contoh: -18.2 dBm"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70">
                            </div>
                        </div>
                    </section>
                </div>

                <section class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#B8D4EC] pb-2.5">
                        <h2 class="text-[clamp(1rem,1.6vw,1.125rem)] font-extrabold text-[#06233B]">Lampiran 5 Foto Wajib Instalasi</h2>
                        <span
                            class="inline-flex items-center rounded-md border px-2.5 py-1 font-mono text-xs font-bold"
                            :class="uploadedPhotosCount() === 5 ? 'border-[#059669] bg-[#D1FAE5] text-[#065F46]' : 'border-[#D97706] bg-[#FEF3C7] text-[#78350F]'"
                            x-text="uploadedPhotosCount() + '/5 FOTO SIAP'"></span>
                    </div>

                    @php
                    $photoItems = [
                    ['field' => 'foto_mac_ont', 'title' => '1. Foto MAC ONT', 'hint' => 'Label serial dan MAC di belakang ONT'],
                    ['field' => 'foto_odp', 'title' => '2. Foto ODP', 'hint' => 'Port ODP dan redaman terpasang'],
                    ['field' => 'foto_kertas_registrasi', 'title' => '3. Foto Kertas Registrasi', 'hint' => 'Formulir registrasi fisik pelanggan'],
                    ['field' => 'foto_ktp_pelanggan', 'title' => '4. Foto KTP Pelanggan', 'hint' => 'Kartu identitas pelanggan'],
                    ['field' => 'foto_rumah_pelanggan', 'title' => '5. Foto Rumah Pelanggan', 'hint' => 'Tampak depan rumah lokasi pasang'],
                    ];
                    @endphp

                    <div class="mt-4 grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach ($photoItems as $item)
                        <div class="flex flex-col justify-between rounded-xl border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] p-3.5">
                            <div>
                                <div class="flex items-start justify-between gap-1.5">
                                    <label for="{{ $item['field'] }}" class="text-xs font-extrabold uppercase text-[#06233B]">
                                        {{ $item['title'] }} <span class="text-[#991B1B]">*</span>
                                    </label>
                                    <span
                                        x-show="photoPreviews['{{ $item['field'] }}']"
                                        x-cloak
                                        class="shrink-0 rounded bg-[#D1FAE5] px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#065F46]">
                                        OK
                                    </span>
                                </div>
                                <p class="mt-0.5 text-[11px] font-medium text-[#2C5270]">{{ $item['hint'] }}</p>

                                <template x-if="photoPreviews['{{ $item['field'] }}']">
                                    <div class="mt-2.5 overflow-hidden rounded-lg border border-[#5B82A6] bg-white">
                                        <img
                                            :src="photoPreviews['{{ $item['field'] }}']"
                                            alt="Pratinjau {{ $item['title'] }}"
                                            class="h-28 w-full object-cover">
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3">
                                <input
                                    id="{{ $item['field'] }}"
                                    name="{{ $item['field'] }}"
                                    type="file"
                                    accept="image/*"
                                    required
                                    @change="handlePhotoSelect($event, '{{ $item['field'] }}')"
                                    class="block w-full text-xs font-semibold text-[#06233B] file:mr-2.5 file:min-h-[44px] file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#005F99] file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-[#054A78]">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>

                <div class="rounded-2xl border-[1.5px] border-[#5B82A6] bg-white p-4 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-4">
                    <div class="mb-3 text-xs font-medium text-[#2C5270] sm:mb-0">
                        Periksa kembali titik Shareloc, redaman optik, dan kelima foto dokumentasi sebelum mengirim data.
                    </div>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex min-h-[50px] w-full shrink-0 items-center justify-center gap-2.5 rounded-xl bg-[#005F99] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                        <svg
                            x-show="submitting"
                            x-cloak
                            class="h-5 w-5 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Mengunggah Foto & Konfigurasi MikroTik...' : 'Kirim Data PSB & Buat Konfigurasi MikroTik'">
                            Kirim Data PSB & Buat Konfigurasi MikroTik
                        </span>
                    </button>
                </div>
            </form>

            <aside class="space-y-4 lg:col-span-4 lg:sticky lg:top-5">
                <div class="rounded-2xl border-2 border-[#072A47] bg-[#072A47] p-4 text-white shadow-sm sm:p-5">
                    <div class="flex items-center justify-between border-b border-[#174E7C] pb-3">
                        <h2 class="text-base font-extrabold text-white">Pratinjau Laporan Telegram</h2>
                        <span class="rounded border border-[#7DD3FC] bg-[#005F99] px-2 py-0.5 font-mono text-[11px] font-bold text-white">
                            [AUTO-NOC]
                        </span>
                    </div>

                    <pre
                        class="mt-3.5 overflow-x-auto rounded-xl border border-[#174E7C] bg-[#051F36] p-3.5 font-mono text-xs leading-relaxed text-[#E0F2FE]"
                        x-text="liveTelegramPreview()"></pre>

                    <div class="mt-3.5 rounded-xl border border-[#174E7C] bg-[#0B3B63] p-3 text-xs text-[#BAE6FD]">
                        <div class="font-bold text-white">Pengiriman Teks & 5 Foto Dokumentasi</div>
                        <p class="mt-1 leading-relaxed">
                            Aplikasi mengisi baris <code class="font-mono text-white">POE/IP</code> dengan username PPPoE atau IP Static dari MikroTik, lalu mengirimkannya bersama album 5 foto ke grup Telegram tim Anda.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    @if ($psbResult !== null)
    <div
        x-show="showResultModal"
        x-cloak
        @keydown.escape.window="showResultModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="psb-result-title">
        <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-5 shadow-2xl sm:p-6">
            @if (($psbResult['status_sync'] ?? '') === 'success')
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#059669] bg-[#D1FAE5] text-[#065F46]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <div>
                    <h3 id="psb-result-title" class="text-base font-extrabold text-[#06233B]">Konfigurasi MikroTik Selesai</h3>
                    <p class="text-xs font-medium text-[#2C5270]">
                        {{ ($psbResult['tipe_koneksi'] ?? '') === 'pppoe' ? 'Aplikasi telah membuat PPPoE Secret di router utama' : 'Aplikasi telah mengaktifkan Simple Queue dan Static ARP' }}
                    </p>
                </div>
            </div>

            <div class="mt-5 rounded-xl border-2 border-[#005F99] bg-[#EDF5FC] p-4">
                <div class="text-xs font-bold uppercase tracking-wider text-[#2C5270]">
                    {{ ($psbResult['tipe_koneksi'] ?? '') === 'pppoe' ? 'Username PPPoE Pelanggan' : 'IP Address Static Pelanggan' }}
                </div>
                <div class="mt-1.5 break-all font-mono text-lg font-extrabold text-[#06233B]">
                    {{ $psbResult['raw_poe_ip'] }}
                </div>

                <button
                    type="button"
                    @click="copyResultToClipboard()"
                    class="mt-3 flex min-h-[46px] w-full items-center justify-center gap-2 rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span x-text="copied ? 'Tersalin ke Clipboard!' : 'Salin POE / IP ke Clipboard'">Salin POE / IP ke Clipboard</span>
                </button>
            </div>
            @else
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#D97706] bg-[#FEF3C7] text-[#78350F]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                    </svg>
                </span>
                <div>
                    <h3 id="psb-result-title" class="text-base font-extrabold text-[#06233B]">Data PSB Tersimpan (Menunggu NOC)</h3>
                    <p class="text-xs font-semibold text-[#78350F]">Aplikasi telah mengirim laporan ke Telegram dengan penanda CEK NOC.</p>
                </div>
            </div>

            <div class="mt-4 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] p-4 text-xs text-[#991B1B]">
                <div class="font-bold">{{ $psbResult['hasil_poe_ip'] }}</div>
                @if (! empty($psbResult['error_log']))
                <div class="mt-1.5 font-mono text-[11px]">Pesan RouterOS: {{ $psbResult['error_log'] }}</div>
                @endif
            </div>
            @endif

            <dl class="mt-4 divide-y divide-[#B8D4EC] text-xs">
                <div class="flex justify-between py-2">
                    <dt class="font-medium text-[#2C5270]">Pelanggan</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['nama_pelanggan'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-medium text-[#2C5270]">ODP / Port</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['odp_nama'] }} / Port {{ $psbResult['port'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-medium text-[#2C5270]">Paket / Sales</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['paket_nama'] }} / {{ $psbResult['sales_kode'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-medium text-[#2C5270]">MAC Address</dt>
                    <dd class="font-mono font-bold text-[#06233B]">{{ $psbResult['mac_address'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-medium text-[#2C5270]">Teknisi</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['nama_teknisi'] }}</dd>
                </div>
            </dl>

            <div class="mt-5">
                <button
                    type="button"
                    @click="showResultModal = false"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl border-[1.5px] border-[#5B82A6] bg-[#EDF5FC] px-4 py-2.5 text-sm font-bold text-[#06233B] hover:bg-[#E0F2FE] active:bg-[#D0E6F8]">
                    Tutup & Input Pelanggan Berikutnya
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function psbFormApp(config) {
        return {
            userName: config.userName || '',
            isNonTeam: Boolean(config.isNonTeam),
            odpMap: config.odpMap || {},
            paketMap: config.paketMap || {},
            salesMap: config.salesMap || {},
            namaPelanggan: config.initialNama || '',
            alamat: config.initialAlamat || '',
            shareloc: config.initialShareloc || '',
            noTlp: config.initialNoTlp || '',
            selectedOdpId: config.initialOdpId || '',
            port: config.initialPort || '',
            selectedPaketId: config.initialPaketId || '',
            selectedSalesId: config.initialSalesId || '',
            macInput: config.initialMac || '',
            brandRouter: config.initialBrand || '',
            jarakKabel: config.initialJarak || '',
            signalOdp: config.initialSignalOdp || '',
            signalOnt: config.initialSignalOnt || '',
            macError: '',
            rekanTeknisi: config.initialRekan || '',
            fetchingGps: false,
            gpsStatus: '',
            gpsError: false,
            photoPreviews: {
                foto_mac_ont: null,
                foto_odp: null,
                foto_kertas_registrasi: null,
                foto_ktp_pelanggan: null,
                foto_rumah_pelanggan: null,
            },
            submitting: false,
            showResultModal: Boolean(config.hasResultModal),
            resultCopyValue: config.resultCopyValue || '',
            copied: false,

            init() {
                if (this.$refs.odpSelect) {
                    new TomSelect(this.$refs.odpSelect, {
                        create: false,
                        sortField: {
                            field: 'text',
                            direction: 'asc'
                        },
                        render: {
                            option: function(data, escape) {
                                const isUpgrade = data.status === 'upgrade';
                                const badgeClass = isUpgrade ?
                                    'bg-[#E0F2FE] text-[#075985] border-[#0284C7]' :
                                    'bg-[#FEF3C7] text-[#78350F] border-[#D97706]';
                                const badgeText = isUpgrade ? '[PPPoE]' : '[IP]';
                                const cleanLabel = data.text.replace(/\s*\[(PPPoE|IP)\]\s*$/, '');

                                return `<div class="flex items-center justify-between py-1">
                                <span class="font-bold text-[#06233B]">${escape(cleanLabel)}</span>
                                <span class="inline-flex items-center rounded border px-2 py-0.5 font-mono text-xs font-bold ${badgeClass}">${badgeText}</span>
                            </div>`;
                            },
                            item: function(data, escape) {
                                const isUpgrade = data.status === 'upgrade';
                                const badgeClass = isUpgrade ?
                                    'bg-[#E0F2FE] text-[#075985] border-[#0284C7]' :
                                    'bg-[#FEF3C7] text-[#78350F] border-[#D97706]';
                                const badgeText = isUpgrade ? '[PPPoE]' : '[IP]';
                                const cleanLabel = data.text.replace(/\s*\[(PPPoE|IP)\]\s*$/, '');

                                return `<div class="flex items-center gap-2">
                                <span class="font-bold text-[#06233B]">${escape(cleanLabel)}</span>
                                <span class="inline-flex items-center rounded border px-1.5 py-0.5 font-mono text-[11px] font-bold ${badgeClass}">${badgeText}</span>
                            </div>`;
                            }
                        },
                        onChange: (val) => {
                            this.selectedOdpId = val;
                        }
                    });
                }

                if (this.macInput) {
                    this.formatMacInput();
                }
            },

            currentOdp() {
                if (!this.selectedOdpId || !this.odpMap[this.selectedOdpId]) {
                    return null;
                }
                return this.odpMap[this.selectedOdpId];
            },

            macHexCount() {
                return (this.macInput.replace(/[^0-9A-Fa-f]/g, '')).length;
            },

            formatMacInput() {
                const rawHex = this.macInput.replace(/[^0-9A-Fa-f]/g, '').toUpperCase().slice(0, 12);
                const pairs = rawHex.match(/.{1,2}/g) || [];
                this.macInput = pairs.join(':');
                if (rawHex.length > 0 && rawHex.length !== 12) {
                    this.macError = 'MAC Address harus tepat 12 digit heksadesimal (0-9, A-F).';
                } else {
                    this.macError = '';
                }
            },

            fetchCurrentGpsLocation() {
                if (!navigator.geolocation) {
                    this.gpsError = true;
                    this.gpsStatus = 'Browser perangkat tidak mendukung GPS otomatis. Tempel link Google Maps secara manual.';
                    return;
                }

                this.fetchingGps = true;
                this.gpsError = false;
                this.gpsStatus = 'Mengambil titik koordinat GPS...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude.toFixed(6);
                        const lng = position.coords.longitude.toFixed(6);
                        this.shareloc = `https://www.google.com/maps?q=${lat},${lng}`;
                        this.fetchingGps = false;
                        this.gpsError = false;
                        this.gpsStatus = `Koordinat GPS terisi (${lat}, ${lng}).`;
                    },
                    () => {
                        this.fetchingGps = false;
                        this.gpsError = true;
                        this.gpsStatus = 'Gagal membaca lokasi GPS. Aktifkan izin lokasi atau tempel link Google Maps.';
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            },

            handlePhotoSelect(event, fieldName) {
                const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                if (this.photoPreviews[fieldName]) {
                    URL.revokeObjectURL(this.photoPreviews[fieldName]);
                }
                this.photoPreviews[fieldName] = file ? URL.createObjectURL(file) : null;
            },

            uploadedPhotosCount() {
                return Object.values(this.photoPreviews).filter(Boolean).length;
            },

            formattedTechnicianPreview() {
                const partner = (this.rekanTeknisi || '').trim();
                return partner ? `${this.userName}&${partner}` : this.userName;
            },

            liveTelegramPreview() {
                const odp = this.currentOdp();
                const paketName = this.paketMap[this.selectedPaketId] || '-';
                const salesObj = this.salesMap[this.selectedSalesId] || null;
                const poePreview = odp ?
                    (odp.status_upgrade === 'upgrade' ?
                        `${odp.kode_odp}_01_${salesObj ? salesObj.kode_pppoe : '00'}_XXXXX_${(this.namaPelanggan || 'PELANGGAN').toUpperCase().replace(/[^A-Z0-9 ]/g, '').trim().replace(/\s+/g, '_')}` :
                        '[Auto-Claim IP dari ' + (salesObj ? salesObj.router : 'Router Distribusi') + ']') :
                    '[Menunggu Pilihan ODP]';

                const lines = [];
                if (this.isNonTeam) {
                    lines.push('[NON-TIM PSB]');
                }
                lines.push(`NAMA         : ${this.namaPelanggan || '-'}`);
                lines.push(`Alamat.      : ${this.alamat || '-'}`);
                lines.push(`SHARELOC     : ${this.shareloc || '-'}`);
                lines.push(`NO.TLP       : ${this.noTlp || '-'}`);
                lines.push(`ODP          : ${odp ? odp.nama_odp : '-'}`);
                lines.push(`PORT         : ${this.port || '-'}`);
                lines.push(`PAKET        : ${paketName}`);
                lines.push(`MAC          : ${this.macInput || '-'}`);
                lines.push(`POE/IP       : ${poePreview}`);
                lines.push(`BRAND ROUTER : ${this.brandRouter || '-'}`);
                lines.push(`JARAK KABEL  : ${this.jarakKabel || '-'}`);
                lines.push(`SIGNAL ODP   : ${this.signalOdp || '-'}`);
                lines.push(`SIGNAL ONT   : ${this.signalOnt || '-'}`);
                lines.push(`SALES        : ${salesObj ? salesObj.kode_ip : 'TANPA SALES'}`);
                lines.push(`NAMA TEKNISI : ${this.formattedTechnicianPreview()}`);
                lines.push('[AUTO-NOC]');
                return lines.join('\n');
            },

            handleFormSubmit(event) {
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }

                if (this.macHexCount() !== 12) {
                    event.preventDefault();
                    this.macError = 'MAC Address wajib berisi tepat 12 digit heksadesimal sebelum dikirim.';
                    document.getElementById('mac_address')?.focus();
                    return;
                }

                this.submitting = true;
            },

            async copyResultToClipboard() {
                if (!this.resultCopyValue) return;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(this.resultCopyValue);
                    } else {
                        // Fallback textarea copy for non-HTTPS LAN environments.
                        const textArea = document.createElement('textarea');
                        textArea.value = this.resultCopyValue;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-9999px';
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                    }
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 2500);
                } catch (e) {
                    console.error('Gagal menyalin ke clipboard', e);
                }
            }
        };
    }
</script>
@endpush