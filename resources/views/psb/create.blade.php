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
    class="min-h-screen pb-24 lg:pb-14"
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
        serverErrors: @js($errors->toArray()),
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
                        <span class="rounded border border-[#7DD3FC] bg-[#0B3B63] px-2 py-0.5 font-mono text-xs font-bold text-[#BAE6FD]">
                            {{ strtoupper($user->role) }}
                        </span>
                    </div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-[#E0F2FE]">
                        <span>Tujuan laporan Telegram:</span>
                        @if (! $isNonTeam && $targetGroup)
                        <span class="inline-flex items-center rounded bg-[#D1FAE5] px-2 py-0.5 font-mono text-xs font-bold text-[#065F46]">
                            {{ $targetGroup->nama_grup }}
                        </span>
                        @else
                        <span class="inline-flex items-center rounded bg-[#FEF3C7] px-2 py-0.5 font-mono text-xs font-bold text-[#78350F]">
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
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC]/80 bg-[#0B3B63] px-3.5 py-2 text-xs font-bold text-white transition hover:bg-[#991B1B] active:bg-[#7F1D1D]">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1600px] px-3.5 pt-4 sm:px-6 sm:pt-5 lg:px-8">
        {{-- 3-Step Outdoor Completion Progress Bar --}}
        <div class="mb-4 grid grid-cols-3 gap-2 rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-2.5 shadow-sm sm:gap-3 sm:p-3.5" aria-label="Progres Pengisian Formulir PSB">
            <div class="flex items-center gap-2 rounded-xl border px-2.5 py-2 text-xs transition" :class="step1Complete() ? 'border-[#059669] bg-[#D1FAE5] text-[#065F46]' : 'border-[#7FA6C9] bg-[#EDF5FC] text-[#06233B]'">
                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-mono text-xs font-extrabold" :class="step1Complete() ? 'bg-[#059669] text-white' : 'bg-[#005F99] text-white'" x-text="step1Complete() ? '✓' : '1'">1</span>
                <div class="min-w-0">
                    <div class="truncate font-extrabold sm:hidden">Data</div>
                    <div class="hidden truncate font-extrabold sm:block">Pelanggan</div>
                    <div class="hidden truncate text-[11px] sm:block">Nama, WA & GPS</div>
                </div>
            </div>

            <div class="flex items-center gap-2 rounded-xl border px-2.5 py-2 text-xs transition" :class="step2Complete() ? 'border-[#059669] bg-[#D1FAE5] text-[#065F46]' : 'border-[#7FA6C9] bg-[#EDF5FC] text-[#06233B]'">
                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-mono text-xs font-extrabold" :class="step2Complete() ? 'bg-[#059669] text-white' : 'bg-[#005F99] text-white'" x-text="step2Complete() ? '✓' : '2'">2</span>
                <div class="min-w-0">
                    <div class="truncate font-extrabold sm:hidden">Optik</div>
                    <div class="hidden truncate font-extrabold sm:block">ODP & Optik</div>
                    <div class="hidden truncate text-[11px] sm:block">MAC & Redaman dBm</div>
                </div>
            </div>

            <div class="flex items-center gap-2 rounded-xl border px-2.5 py-2 text-xs transition" :class="uploadedPhotosCount() === 5 ? 'border-[#059669] bg-[#D1FAE5] text-[#065F46]' : 'border-[#7FA6C9] bg-[#EDF5FC] text-[#06233B]'">
                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-mono text-xs font-extrabold" :class="uploadedPhotosCount() === 5 ? 'bg-[#059669] text-white' : 'bg-[#005F99] text-white'" x-text="uploadedPhotosCount() === 5 ? '✓' : '3'">3</span>
                <div class="min-w-0">
                    <div class="truncate font-extrabold" x-text="uploadedPhotosCount() + '/5 Foto'">0/5 Foto</div>
                    <div class="hidden truncate text-[11px] sm:block">Kompresi Otomatis</div>
                </div>
            </div>
        </div>

        <template x-if="errorList().length > 0">
            <div id="psb-error-summary" class="mb-4 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] p-3.5 text-xs text-[#991B1B]" role="alert" tabindex="-1">
                <div class="font-bold">Periksa kembali isian formulir PSB berikut (foto yang sudah Anda pilih tetap aman):</div>
                <ul class="mt-1.5 list-inside list-disc space-y-1 font-semibold">
                    <template x-for="(msg, index) in errorList()" :key="index">
                        <li x-text="msg"></li>
                    </template>
                </ul>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
            <form
                x-ref="psbForm"
                method="POST"
                action="{{ route('psb.store') }}"
                enctype="multipart/form-data"
                @submit="handleFormSubmit($event)"
                class="space-y-4 sm:space-y-5 lg:col-span-8"
                novalidate>
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:gap-5 md:grid-cols-2">
                    {{-- SECTION 1: CUSTOMER & LOCATION --}}
                    <section class="flex flex-col justify-between rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-[#7FA6C9] pb-2.5">
                                <h2 class="text-[clamp(1rem,1.6vw,1.125rem)] font-extrabold text-[#06233B]">1. Data Pelanggan & Lokasi</h2>
                                <span class="rounded bg-[#EDF5FC] px-2 py-0.5 font-mono text-xs font-bold text-[#005F99]">Wajib *</span>
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
                                    :aria-invalid="hasFieldError('nama_pelanggan') ? 'true' : 'false'"
                                    placeholder="Contoh: ASTONI / NURHAIDA"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 text-sm font-semibold text-[#06233B]"
                                    :class="hasFieldError('nama_pelanggan') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                <p x-show="hasFieldError('nama_pelanggan')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('nama_pelanggan')"></p>
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
                                    :aria-invalid="hasFieldError('no_tlp') ? 'true' : 'false'"
                                    placeholder="Contoh: 081234567890"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 text-sm font-semibold text-[#06233B]"
                                    :class="hasFieldError('no_tlp') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                <p x-show="hasFieldError('no_tlp')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('no_tlp')"></p>
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
                                    :aria-invalid="hasFieldError('alamat') ? 'true' : 'false'"
                                    placeholder="Tulis alamat rumah atau patokan lokasi pemasangan"
                                    class="mt-1.5 block w-full rounded-lg border-[1.5px] px-3.5 py-2.5 text-sm font-semibold text-[#06233B]"
                                    :class="hasFieldError('alamat') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'"></textarea>
                                <p x-show="hasFieldError('alamat')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('alamat')"></p>
                            </div>

                            <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] p-3.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label for="shareloc" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                        Link Shareloc / Koordinat GPS <span class="text-[#991B1B]">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="fetchCurrentGpsLocation()"
                                        :disabled="fetchingGps"
                                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg bg-[#005F99] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E] disabled:opacity-60">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span x-text="fetchingGps ? 'Mengambil GPS...' : 'Ambil Titik GPS Saat Ini'">Ambil Titik GPS Saat Ini</span>
                                    </button>
                                </div>
                                <input
                                    id="shareloc"
                                    name="shareloc"
                                    type="url"
                                    x-model="shareloc"
                                    required
                                    :aria-invalid="hasFieldError('shareloc') ? 'true' : 'false'"
                                    placeholder="https://www.google.com/maps?q=-6.200,106.816"
                                    class="mt-2 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2 font-mono text-xs font-bold text-[#06233B]"
                                    :class="hasFieldError('shareloc') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                <p x-show="gpsStatus !== ''" x-cloak class="mt-1.5 text-xs font-bold" :class="gpsError ? 'text-[#991B1B]' : 'text-[#065F46]'" x-text="gpsStatus"></p>
                                <p x-show="hasFieldError('shareloc')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('shareloc')"></p>
                            </div>

                            <div>
                                <label for="sales_id" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Kode Sales (Pilih Sales atau Tanpa Sales)
                                </label>
                                <select
                                    id="sales_id"
                                    name="sales_id"
                                    x-model="selectedSalesId"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3.5 py-2.5 text-sm font-semibold text-[#06233B]">
                                    <option value="">-- Tanpa Sales (Langsung / Kantor) --</option>
                                    @foreach ($salesList as $s)
                                    <option value="{{ $s->id }}">
                                        {{ $s->kode_sales_ip }} - {{ $s->nama_sales }} (PPPoE: {{ $s->kode_sales_pppoe }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-[#7FA6C9] pt-4">
                            <label for="rekan_teknisi" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                Rekan Teknisi (Opsional)
                            </label>
                            <input
                                id="rekan_teknisi"
                                name="rekan_teknisi"
                                type="text"
                                x-model="rekanTeknisi"
                                placeholder="Kosongkan jika sendiri (contoh: nopal)"
                                class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3.5 py-2.5 text-sm font-semibold text-[#06233B]">
                            <p class="mt-1.5 text-xs font-semibold text-[#2C5270]">
                                Nama teknisi di laporan: <span class="font-mono font-bold text-[#06233B]" x-text="formattedTechnicianPreview()"></span>
                            </p>
                        </div>
                    </section>

                    {{-- SECTION 2: ODP, PACKAGE & OPTICAL TELEMETRY --}}
                    <section class="space-y-4 rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
                        <div class="border-b border-[#7FA6C9] pb-2.5">
                            <h2 class="text-[clamp(1rem,1.6vw,1.125rem)] font-extrabold text-[#06233B]">2. ODP, Paket & Parameter Optik ONT</h2>
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
                                        @selected((string) old('odp_id') === (string) $odp->id)>
                                        {{ $odp->nama_odp }} {{ $odp->status_upgrade === 'upgrade' ? '[PPPoE]' : '[IP]' }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <p x-show="hasFieldError('odp_id')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('odp_id')"></p>

                            <template x-if="currentOdp()">
                                <div class="mt-2 flex items-center justify-between rounded-lg border border-[#4A7396] bg-[#EDF5FC] px-3 py-2 text-xs">
                                    <span class="font-bold text-[#2C5270]">Mode konfigurasi otomatis:</span>
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
                                    :aria-invalid="hasFieldError('port') ? 'true' : 'false'"
                                    placeholder="1 s/d 32"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 font-mono text-sm font-bold text-[#06233B]"
                                    :class="hasFieldError('port') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                <p x-show="hasFieldError('port')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('port')"></p>
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
                                    :aria-invalid="hasFieldError('paket_id') ? 'true' : 'false'"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 text-sm font-semibold text-[#06233B]"
                                    :class="hasFieldError('paket_id') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                    <option value="">-- Pilih Paket --</option>
                                    @foreach ($pakets as $paket)
                                    <option value="{{ $paket->id }}">
                                        {{ $paket->nama_paket }} ({{ $paket->queue_max_limit }})
                                    </option>
                                    @endforeach
                                </select>
                                <p x-show="hasFieldError('paket_id')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('paket_id')"></p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="mac_address" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    MAC Address ONT / Router <span class="text-[#991B1B]">*</span>
                                </label>
                                <span
                                    class="rounded border px-2 py-0.5 font-mono text-xs font-bold"
                                    :class="macHexCount() === 12 ? 'border-[#059669] bg-[#D1FAE5] text-[#065F46]' : 'border-[#7FA6C9] bg-[#EDF5FC] text-[#06233B]'"
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
                                autocapitalize="characters"
                                spellcheck="false"
                                :aria-invalid="(macError !== '' || hasFieldError('mac_address')) ? 'true' : 'false'"
                                placeholder="F8:14:FE:87:6D:0A atau f814fe876d0a"
                                class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 font-mono text-sm font-bold uppercase text-[#06233B]"
                                :class="(macError !== '' || hasFieldError('mac_address')) ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                            <p x-show="macError !== ''" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="macError"></p>
                            <p x-show="macError === '' && hasFieldError('mac_address')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('mac_address')"></p>
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
                                    list="brand_router_presets"
                                    placeholder="ZTE F609 / Huawei"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 text-sm font-semibold text-[#06233B]"
                                    :class="hasFieldError('brand_router') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                <datalist id="brand_router_presets">
                                    <option value="ZTE F609"></option>
                                    <option value="ZTE F670L"></option>
                                    <option value="Huawei HG8245H"></option>
                                    <option value="Huawei EG8145V5"></option>
                                    <option value="FiberHome HG6145D2"></option>
                                    <option value="Nokia G-2425G-A"></option>
                                </datalist>
                                <p x-show="hasFieldError('brand_router')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('brand_router')"></p>
                            </div>

                            <div>
                                <label for="jarak_kabel" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                    Jarak Kabel Dropcore <span class="text-[#991B1B]">*</span>
                                </label>
                                <div class="relative mt-1.5 flex items-center">
                                    <input
                                        id="jarak_kabel"
                                        name="jarak_kabel"
                                        type="text"
                                        inputmode="numeric"
                                        x-model="jarakKabel"
                                        @blur="normalizeDistanceUnit()"
                                        required
                                        placeholder="Contoh: 120m"
                                        class="block min-h-[46px] w-full rounded-lg border-[1.5px] py-2.5 pl-3.5 pr-12 font-mono text-sm font-bold text-[#06233B]"
                                        :class="hasFieldError('jarak_kabel') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                    <span class="pointer-events-none absolute right-3 font-mono text-xs font-bold text-[#2C5270]">meter</span>
                                </div>
                                <p x-show="hasFieldError('jarak_kabel')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('jarak_kabel')"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <div>
                                <div class="flex items-center justify-between gap-1">
                                    <label for="signal_odp" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                        Signal ODP (dBm) <span class="text-[#991B1B]">*</span>
                                    </label>
                                    <template x-if="opticalBadge(signalOdp)">
                                        <span class="rounded border px-1.5 py-0.5 font-mono text-[11px] font-bold" :class="opticalBadge(signalOdp).classes" x-text="opticalBadge(signalOdp).label"></span>
                                    </template>
                                </div>
                                <input
                                    id="signal_odp"
                                    name="signal_odp"
                                    type="text"
                                    inputmode="decimal"
                                    x-model="signalOdp"
                                    @blur="signalOdp = normalizeOpticalValue(signalOdp)"
                                    required
                                    placeholder="Contoh: -17.4 dBm"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 font-mono text-sm font-bold text-[#06233B]"
                                    :class="hasFieldError('signal_odp') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                <p x-show="hasFieldError('signal_odp')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('signal_odp')"></p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-1">
                                    <label for="signal_ont" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                                        Signal ONT (dBm) <span class="text-[#991B1B]">*</span>
                                    </label>
                                    <template x-if="opticalBadge(signalOnt)">
                                        <span class="rounded border px-1.5 py-0.5 font-mono text-[11px] font-bold" :class="opticalBadge(signalOnt).classes" x-text="opticalBadge(signalOnt).label"></span>
                                    </template>
                                </div>
                                <input
                                    id="signal_ont"
                                    name="signal_ont"
                                    type="text"
                                    inputmode="decimal"
                                    x-model="signalOnt"
                                    @blur="signalOnt = normalizeOpticalValue(signalOnt)"
                                    required
                                    placeholder="Contoh: -18.2 dBm"
                                    class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] px-3.5 py-2.5 font-mono text-sm font-bold text-[#06233B]"
                                    :class="hasFieldError('signal_ont') ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#4A7396] bg-white'">
                                <p x-show="hasFieldError('signal_ont')" x-cloak class="mt-1 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('signal_ont')"></p>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- SECTION 3: 5 MANDATORY INSTALLATION PHOTOS WITH AUTO-COMPRESSION --}}
                <section class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#7FA6C9] pb-2.5">
                        <div>
                            <h2 class="text-[clamp(1rem,1.6vw,1.125rem)] font-extrabold text-[#06233B]">3. Lampiran 5 Foto Wajib Instalasi</h2>
                            <p class="text-xs font-medium text-[#2C5270]">Foto kamera dikompresi otomatis di perangkat Anda agar hemat kuota & cepat terkirim di jaringan lapangan.</p>
                        </div>
                        <span
                            class="inline-flex items-center rounded-md border px-2.5 py-1 font-mono text-xs font-bold"
                            :class="uploadedPhotosCount() === 5 ? 'border-[#059669] bg-[#D1FAE5] text-[#065F46]' : 'border-[#D97706] bg-[#FEF3C7] text-[#78350F]'"
                            x-text="uploadedPhotosCount() + '/5 FOTO SIAP'"></span>
                    </div>

                    @php
                    $photoItems = [
                        ['field' => 'foto_mac_ont', 'title' => '1. Foto MAC ONT', 'hint' => 'Label serial & MAC di belakang ONT'],
                        ['field' => 'foto_odp', 'title' => '2. Foto ODP', 'hint' => 'Port ODP & redaman OPM terpasang'],
                        ['field' => 'foto_kertas_registrasi', 'title' => '3. Foto Kertas Registrasi', 'hint' => 'Formulir registrasi fisik pelanggan'],
                        ['field' => 'foto_ktp_pelanggan', 'title' => '4. Foto KTP Pelanggan', 'hint' => 'Disimpan terenkripsi privat (bukan publik)'],
                        ['field' => 'foto_rumah_pelanggan', 'title' => '5. Foto Rumah Pelanggan', 'hint' => 'Tampak depan rumah lokasi pasang'],
                    ];
                    @endphp

                    <div class="mt-4 grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
                        @foreach ($photoItems as $item)
                        <div
                            class="flex flex-col justify-between rounded-xl border-[1.5px] p-3.5 transition"
                            :class="hasFieldError('{{ $item['field'] }}') ? 'border-[#DC2626] bg-[#FEF2F2]' : (photoPreviews['{{ $item['field'] }}'] ? 'border-[#059669] bg-[#EDF5FC]' : 'border-[#4A7396] bg-[#EDF5FC]')">
                            <div>
                                <div class="flex items-start justify-between gap-1.5">
                                    <label for="{{ $item['field'] }}" class="text-xs font-extrabold uppercase text-[#06233B]">
                                        {{ $item['title'] }} <span class="text-[#991B1B]">*</span>
                                    </label>
                                    <span
                                        x-show="photoMeta['{{ $item['field'] }}']"
                                        x-cloak
                                        class="shrink-0 rounded border border-[#059669] bg-[#D1FAE5] px-1.5 py-0.5 font-mono text-[11px] font-bold text-[#065F46]"
                                        x-text="photoMeta['{{ $item['field'] }}']">
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs font-medium text-[#2C5270]">{{ $item['hint'] }}</p>

                                <template x-if="photoPreviews['{{ $item['field'] }}']">
                                    <div class="mt-2.5 overflow-hidden rounded-lg border border-[#4A7396] bg-white">
                                        <img
                                            :src="photoPreviews['{{ $item['field'] }}']"
                                            alt="Pratinjau {{ $item['title'] }}"
                                            class="h-32 w-full object-cover">
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3">
                                <input
                                    id="{{ $item['field'] }}"
                                    name="{{ $item['field'] }}"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                    @change="handlePhotoSelect($event, '{{ $item['field'] }}')"
                                    class="block w-full text-xs font-semibold text-[#06233B] file:mr-2.5 file:min-h-[44px] file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#005F99] file:px-3.5 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-[#054A78]">
                                <p x-show="hasFieldError('{{ $item['field'] }}')" x-cloak class="mt-1.5 text-xs font-bold text-[#991B1B]" x-text="firstFieldError('{{ $item['field'] }}')"></p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>

                {{-- SUBMIT ACTION & LIVE UPLOAD PROGRESS BAR --}}
                <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm">
                    <div x-show="submitting" x-cloak class="mb-3.5">
                        <div class="flex items-center justify-between text-xs font-bold text-[#06233B]">
                            <span x-text="uploadProgress < 100 ? 'Mengunggah 5 Foto Dokumentasi...' : 'Menghubungi RouterOS MikroTik & Telegram...'"></span>
                            <span class="font-mono" x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="mt-1.5 h-3 w-full overflow-hidden rounded-full border border-[#4A7396] bg-[#EDF5FC]">
                            <div
                                class="h-full bg-[#005F99] transition-all duration-200"
                                :style="`width: ${uploadProgress}%`"></div>
                        </div>
                    </div>

                    <div class="sm:flex sm:items-center sm:justify-between sm:gap-4">
                        <div class="mb-3 text-xs font-medium text-[#2C5270] sm:mb-0">
                            Pastikan titik Shareloc, redaman optik, dan kelima foto dokumentasi sudah sesuai sebelum dikirim.
                        </div>
                        <button
                            type="submit"
                            :disabled="submitting || compressingCount > 0"
                            class="flex min-h-[50px] w-full shrink-0 items-center justify-center gap-2.5 rounded-xl bg-[#005F99] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                            <svg
                                x-show="submitting || compressingCount > 0"
                                x-cloak
                                class="h-5 w-5 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span x-text="compressingCount > 0 ? 'Mengompresi Foto...' : (submitting ? 'Memproses Konfigurasi PSB...' : 'Kirim Data PSB & Buat Konfigurasi MikroTik')">
                                Kirim Data PSB & Buat Konfigurasi MikroTik
                            </span>
                        </button>
                    </div>
                </div>
            </form>

            {{-- DESKTOP STICKY TELEGRAM PREVIEW --}}
            <aside class="hidden space-y-4 lg:col-span-4 lg:sticky lg:top-5 lg:block">
                <div class="rounded-2xl border-2 border-[#072A47] bg-[#072A47] p-4 text-white shadow-sm sm:p-5">
                    <div class="flex items-center justify-between border-b border-[#174E7C] pb-3">
                        <h2 class="text-base font-extrabold text-white">Pratinjau Laporan Telegram</h2>
                        <span class="rounded border border-[#7DD3FC] bg-[#005F99] px-2 py-0.5 font-mono text-xs font-bold text-white">
                            [AUTO-NOC]
                        </span>
                    </div>

                    <pre
                        class="mt-3.5 overflow-x-auto rounded-xl border border-[#174E7C] bg-[#051F36] p-3.5 font-mono text-xs leading-relaxed text-[#E0F2FE]"
                        x-text="liveTelegramPreview()"></pre>

                    <div class="mt-3.5 rounded-xl border border-[#174E7C] bg-[#0B3B63] p-3 text-xs text-[#E0F2FE]">
                        <div class="font-bold text-white">Pengiriman Teks & 5 Foto Dokumentasi</div>
                        <p class="mt-1 leading-relaxed">
                            Aplikasi mengisi baris <code class="font-mono text-white">POE/IP</code> dengan username PPPoE atau IP Static dari MikroTik, lalu mengirimkannya bersama album 5 foto ke grup Telegram tim Anda.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    {{-- MOBILE STICKY BOTTOM BAR FOR INSTANT TELEGRAM PREVIEW --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-[#124670] bg-[#072A47] px-3.5 py-2.5 text-white shadow-lg lg:hidden">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-2">
            <div class="min-w-0 text-xs">
                <div class="font-extrabold text-white" x-text="uploadedPhotosCount() + '/5 Foto · ' + (currentOdp() ? (currentOdp().status_upgrade === 'upgrade' ? 'Mode PPPoE' : 'Mode Static IP') : 'ODP Belum Dipilih')"></div>
                <div class="truncate text-[#BAE6FD]" x-text="namaPelanggan ? ('Pelanggan: ' + namaPelanggan) : 'Isi data & cek pratinjau Telegram'"></div>
            </div>
            <button
                type="button"
                @click="showMobilePreview = true"
                class="inline-flex min-h-[44px] shrink-0 items-center gap-1.5 rounded-xl border border-[#7DD3FC] bg-[#005F99] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#054A78]">
                <span>Cek Laporan</span>
            </button>
        </div>
    </div>

    {{-- MOBILE TELEGRAM PREVIEW DRAWER --}}
    <div
        x-show="showMobilePreview"
        x-cloak
        x-trap.inert.noscroll="showMobilePreview"
        @keydown.escape.window="showMobilePreview = false"
        class="fixed inset-0 z-50 flex items-end justify-center bg-[#06233B]/80 p-3 sm:items-center lg:hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="mobile-preview-title">
        <div class="w-full max-w-lg rounded-2xl border-2 border-[#7DD3FC] bg-[#072A47] p-4 text-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-[#174E7C] pb-2.5">
                <h3 id="mobile-preview-title" class="text-sm font-extrabold">Pratinjau Laporan Telegram [AUTO-NOC]</h3>
                <button
                    type="button"
                    @click="showMobilePreview = false"
                    class="min-h-[44px] rounded-lg border border-[#7DD3FC] bg-[#0B3B63] px-3 py-1.5 text-xs font-bold text-white">
                    Tutup
                </button>
            </div>
            <pre
                class="mt-3 max-h-[60vh] overflow-auto rounded-xl border border-[#174E7C] bg-[#051F36] p-3 font-mono text-xs leading-relaxed text-[#E0F2FE]"
                x-text="liveTelegramPreview()"></pre>
        </div>
    </div>

    {{-- PROVISIONING RESULT MODAL --}}
    @if ($psbResult !== null)
    <div
        x-show="showResultModal"
        x-cloak
        x-trap.inert.noscroll="showResultModal"
        @keydown.escape.window="showResultModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="psb-result-title">
        <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-5 shadow-2xl sm:p-6">
            @if (($psbResult['status_sync'] ?? '') === 'success')
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#059669] bg-[#D1FAE5] text-[#065F46]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
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
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span x-text="copied ? 'Tersalin ke Clipboard!' : 'Salin POE / IP ke Clipboard'">Salin POE / IP ke Clipboard</span>
                </button>
            </div>
            @else
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#D97706] bg-[#FEF3C7] text-[#78350F]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
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
                <div class="mt-1.5 font-mono text-xs">Pesan RouterOS: {{ $psbResult['error_log'] }}</div>
                @endif
            </div>
            @endif

            <dl class="mt-4 divide-y divide-[#7FA6C9] text-xs">
                <div class="flex justify-between py-2">
                    <dt class="font-semibold text-[#2C5270]">Pelanggan</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['nama_pelanggan'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-semibold text-[#2C5270]">ODP / Port</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['odp_nama'] }} / Port {{ $psbResult['port'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-semibold text-[#2C5270]">Paket / Sales</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['paket_nama'] }} / {{ $psbResult['sales_kode'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-semibold text-[#2C5270]">MAC Address</dt>
                    <dd class="font-mono font-bold text-[#06233B]">{{ $psbResult['mac_address'] }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="font-semibold text-[#2C5270]">Teknisi</dt>
                    <dd class="font-bold text-[#06233B]">{{ $psbResult['nama_teknisi'] }}</dd>
                </div>
            </dl>

            <div class="mt-5">
                <button
                    type="button"
                    @click="showResultModal = false"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-4 py-2.5 text-sm font-bold text-[#06233B] hover:bg-[#E0F2FE] active:bg-[#D0E6F8]">
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
    window.psbFormApp = function psbFormApp(config) {
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
            fieldErrors: config.serverErrors || {},
            fetchingGps: false,
            gpsStatus: '',
            gpsError: false,
            compressingCount: 0,
            uploadProgress: 0,
            showMobilePreview: false,
            photoPreviews: {
                foto_mac_ont: null,
                foto_odp: null,
                foto_kertas_registrasi: null,
                foto_ktp_pelanggan: null,
                foto_rumah_pelanggan: null,
            },
            photoMeta: {
                foto_mac_ont: '',
                foto_odp: '',
                foto_kertas_registrasi: '',
                foto_ktp_pelanggan: '',
                foto_rumah_pelanggan: '',
            },
            submitting: false,
            showResultModal: Boolean(config.hasResultModal),
            resultCopyValue: config.resultCopyValue || '',
            copied: false,

            init() {
                if (this.$refs.odpSelect && window.TomSelect) {
                    new window.TomSelect(this.$refs.odpSelect, {
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
                                    <span class="inline-flex items-center rounded border px-1.5 py-0.5 font-mono text-xs font-bold ${badgeClass}">${badgeText}</span>
                                </div>`;
                            }
                        },
                        onChange: (val) => {
                            this.selectedOdpId = val;
                            delete this.fieldErrors.odp_id;
                        }
                    });
                }

                if (this.macInput) {
                    this.formatMacInput();
                }
            },

            step1Complete() {
                return Boolean(
                    this.namaPelanggan.trim() &&
                    this.noTlp.trim() &&
                    this.alamat.trim() &&
                    this.shareloc.trim()
                );
            },

            step2Complete() {
                return Boolean(
                    this.selectedOdpId &&
                    this.port &&
                    this.selectedPaketId &&
                    this.macHexCount() === 12 &&
                    this.brandRouter.trim() &&
                    this.jarakKabel.trim() &&
                    this.signalOdp.trim() &&
                    this.signalOnt.trim()
                );
            },

            hasFieldError(field) {
                return Array.isArray(this.fieldErrors[field]) && this.fieldErrors[field].length > 0;
            },

            firstFieldError(field) {
                return this.hasFieldError(field) ? this.fieldErrors[field][0] : '';
            },

            errorList() {
                return Object.values(this.fieldErrors || {}).flat();
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
                    delete this.fieldErrors.mac_address;
                }
            },

            normalizeDistanceUnit() {
                const val = (this.jarakKabel || '').trim();
                if (/^\d+$/.test(val)) {
                    this.jarakKabel = `${val}m`;
                }
            },

            normalizeOpticalValue(rawVal) {
                const trimmed = (rawVal || '').trim().replace(',', '.');
                if (trimmed === '') return '';
                const match = trimmed.match(/^(-?\d+(?:\.\d+)?)/);
                if (!match) return trimmed;
                let num = parseFloat(match[1]);
                if (isNaN(num)) return trimmed;
                // Field OPM readings for FTTH are negative dBm; auto-negate positive shorthand like "17.4"
                if (num > 0 && num <= 45) {
                    num = -num;
                }
                return `${num} dBm`;
            },

            opticalBadge(rawVal) {
                const trimmed = (rawVal || '').trim().replace(',', '.');
                const match = trimmed.match(/^(-?\d+(?:\.\d+)?)/);
                if (!match) return null;
                let num = parseFloat(match[1]);
                if (isNaN(num)) return null;
                if (num > 0 && num <= 45) num = -num;

                if (num >= -22.9 && num <= -8) {
                    return {
                        label: 'Sinyal Bagus',
                        classes: 'border-[#059669] bg-[#D1FAE5] text-[#065F46]'
                    };
                }
                if (num >= -24.9 && num < -22.9) {
                    return {
                        label: 'Batas Wajar',
                        classes: 'border-[#D97706] bg-[#FEF3C7] text-[#78350F]'
                    };
                }
                return {
                    label: 'Redaman Tinggi!',
                    classes: 'border-[#DC2626] bg-[#FEE2E2] text-[#991B1B]'
                };
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
                        delete this.fieldErrors.shareloc;
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

            async handlePhotoSelect(event, fieldName) {
                const input = event.target;
                const file = input.files && input.files[0] ? input.files[0] : null;

                if (this.photoPreviews[fieldName]) {
                    URL.revokeObjectURL(this.photoPreviews[fieldName]);
                }

                if (!file) {
                    this.photoPreviews[fieldName] = null;
                    this.photoMeta[fieldName] = '';
                    return;
                }

                delete this.fieldErrors[fieldName];
                this.compressingCount++;

                try {
                    const finalFile = await this.compressImageFile(file);
                    if (finalFile !== file && window.DataTransfer) {
                        const dt = new DataTransfer();
                        dt.items.add(finalFile);
                        input.files = dt.files;
                    }
                    this.photoPreviews[fieldName] = URL.createObjectURL(finalFile);
                    const kb = Math.max(1, Math.round(finalFile.size / 1024));
                    this.photoMeta[fieldName] = kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${kb} KB`;
                } catch {
                    this.photoPreviews[fieldName] = URL.createObjectURL(file);
                    const kb = Math.max(1, Math.round(file.size / 1024));
                    this.photoMeta[fieldName] = `${kb} KB`;
                } finally {
                    this.compressingCount = Math.max(0, this.compressingCount - 1);
                }
            },

            compressImageFile(file) {
                return new Promise((resolve) => {
                    if (!file.type.startsWith('image/') || file.size <= 450 * 1024) {
                        resolve(file);
                        return;
                    }

                    const img = new Image();
                    const objectUrl = URL.createObjectURL(file);

                    img.onload = () => {
                        URL.revokeObjectURL(objectUrl);
                        const maxDim = 1600;
                        let { width, height } = img;

                        if (width > maxDim || height > maxDim) {
                            if (width >= height) {
                                height = Math.round((height * maxDim) / width);
                                width = maxDim;
                            } else {
                                width = Math.round((width * maxDim) / height);
                                height = maxDim;
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        if (!ctx) {
                            resolve(file);
                            return;
                        }
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob(
                            (blob) => {
                                if (!blob || blob.size >= file.size) {
                                    resolve(file);
                                    return;
                                }
                                const baseName = file.name.replace(/\.[^.]+$/, '') || 'psb-photo';
                                const compressed = new File([blob], `${baseName}.jpg`, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now(),
                                });
                                resolve(compressed);
                            },
                            'image/jpeg',
                            0.82
                        );
                    };

                    img.onerror = () => {
                        URL.revokeObjectURL(objectUrl);
                        resolve(file);
                    };

                    img.src = objectUrl;
                });
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
                if (this.submitting || this.compressingCount > 0) {
                    event.preventDefault();
                    return;
                }

                if (this.macHexCount() !== 12) {
                    event.preventDefault();
                    this.macError = 'MAC Address wajib berisi tepat 12 digit heksadesimal sebelum dikirim.';
                    document.getElementById('mac_address')?.focus();
                    return;
                }

                // Progressive XHR upload preserves selected photos if server validation returns 422
                if (!window.XMLHttpRequest || !window.FormData) {
                    this.submitting = true;
                    return;
                }

                event.preventDefault();
                this.submitting = true;
                this.uploadProgress = 5;
                this.fieldErrors = {};

                const formEl = this.$refs.psbForm;
                const formData = new FormData(formEl);
                const xhr = new XMLHttpRequest();

                xhr.open('POST', formEl.action, true);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable && e.total > 0) {
                        this.uploadProgress = Math.min(98, Math.max(5, Math.round((e.loaded / e.total) * 100)));
                    }
                };

                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        this.uploadProgress = 100;
                        try {
                            const data = JSON.parse(xhr.responseText);
                            window.location.assign(data.redirect || formEl.action);
                            return;
                        } catch {
                            window.location.reload();
                            return;
                        }
                    }

                    this.submitting = false;
                    this.uploadProgress = 0;

                    if (xhr.status === 422) {
                        try {
                            const payload = JSON.parse(xhr.responseText);
                            this.fieldErrors = payload.errors || {};
                            this.$nextTick(() => {
                                document.getElementById('psb-error-summary')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            });
                            return;
                        } catch {
                            // Fall through
                        }
                    }

                    this.fieldErrors = {
                        general: ['Gagal mengirim data PSB (Status ' + xhr.status + '). Periksa koneksi internet Anda lalu coba lagi.']
                    };
                };

                xhr.onerror = () => {
                    this.submitting = false;
                    this.uploadProgress = 0;
                    this.fieldErrors = {
                        general: ['Koneksi jaringan terputus saat mengunggah. Foto Anda masih tersimpan di halaman ini, silakan tekan Kirim kembali.']
                    };
                };

                xhr.send(formData);
            },

            async copyResultToClipboard() {
                if (!this.resultCopyValue) return;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(this.resultCopyValue);
                    } else {
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
    };
</script>
@endpush
