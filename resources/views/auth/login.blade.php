@extends('layouts.app', ['title' => 'Masuk Akun Teknisi & NOC - ISP Auto-PSB'])

@section('content')
<div class="min-h-screen lg:grid lg:grid-cols-12" x-data="{ submitting: false }">
    <div class="hidden flex-col justify-between bg-[#072A47] p-10 text-white lg:col-span-7 lg:flex xl:p-14">
        <div>
            <div class="inline-flex items-center gap-3 rounded-xl border border-[#174E7C] bg-[#0B3B63] px-4 py-2.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#7DD3FC] bg-[#005F99] font-mono text-xs font-extrabold text-white">
                    PSB
                </span>
                <div>
                    <div class="text-sm font-bold text-white">ISP Auto-PSB</div>
                    <div class="text-xs text-[#BAE6FD]">RouterOS API & Laporan Telegram</div>
                </div>
            </div>

            <h1 class="mt-9 text-[clamp(1.75rem,2.6vw,2.35rem)] font-extrabold leading-tight tracking-tight text-white">
                Input Pasang Baru (PSB) & Konfigurasi RouterOS Langsung dari Lokasi Pelanggan.
            </h1>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-[#E0F2FE]">
                Teknisi mengisi data pelanggan, titik koordinat Shareloc, dan 5 foto instalasi. Aplikasi membuat akun PPPoE atau mengaktifkan Simple Queue dan Static ARP di MikroTik, lalu mengirim laporannya ke grup Telegram tim.
            </p>

            <div class="mt-8 max-w-xl space-y-4">
                <div class="rounded-xl border border-[#174E7C] bg-[#0B3B63] p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold text-[#BAE6FD]">PENENTUAN MODE BERDASARKAN ODP</span>
                        <span class="rounded border border-[#7DD3FC] bg-[#005F99] px-2 py-0.5 font-mono text-[11px] font-bold text-white">
                            RouterOS v6 / v7
                        </span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-white">
                        Saat Anda memilih ODP berstatus Upgrade [PPPoE], aplikasi membuat PPP Secret baru dengan nomor urut otomatis. Untuk ODP Belum Upgrade [IP], aplikasi mengambil slot Simple Queue kosong dan mengikat MAC Address ONT ke Static ARP.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-[#174E7C] bg-[#051F36] p-4">
                        <div class="font-mono text-xs font-bold text-[#BAE6FD]">PENGECEKAN SLOT LANGSUNG</div>
                        <p class="mt-1 text-xs leading-relaxed text-[#E0F2FE]">
                            Sebelum memakai IP Static, aplikasi memeriksa antrean CLIENT-*, tabel Static ARP, dan daftar alamat EXPIRED di MikroTik.
                        </p>
                    </div>
                    <div class="rounded-xl border border-[#174E7C] bg-[#051F36] p-4">
                        <div class="font-mono text-xs font-bold text-[#BAE6FD]">CADANGAN LAPORAN NOC</div>
                        <p class="mt-1 text-xs leading-relaxed text-[#E0F2FE]">
                            Apabila router sedang tidak terjangkau, aplikasi tetap menyimpan data PSB dan mengirim laporan ke Telegram dengan penanda CEK NOC.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-[#174E7C] pt-6 text-xs text-[#BAE6FD]">
            Browser ponsel menyimpan sesi login Anda selama 30 hari (<code class="font-mono text-white">SESSION_LIFETIME=43200</code>) agar Anda tidak perlu masuk berulang kali di lapangan.
        </div>
    </div>

    <div class="flex min-h-screen flex-col justify-center bg-[#EDF5FC] px-4 py-8 sm:px-8 lg:col-span-5 lg:px-10 xl:px-14">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-5 flex items-center gap-3 rounded-xl bg-[#072A47] p-3.5 text-white lg:hidden">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#7DD3FC] bg-[#005F99] font-mono text-xs font-extrabold text-white">
                    PSB
                </span>
                <div>
                    <div class="text-base font-bold text-white">ISP Auto-PSB</div>
                    <div class="text-xs text-[#BAE6FD]">Formulir Teknisi & Panel NOC</div>
                </div>
            </div>

            <div class="rounded-2xl border-2 border-[#5B82A6] bg-white p-5 shadow-sm sm:p-8">
                <h2 class="text-[clamp(1.15rem,2vw,1.35rem)] font-extrabold text-[#06233B]">Masuk ke Akun Anda</h2>
                <p class="mt-1 text-xs font-medium text-[#2C5270]">
                    Masukkan username dan password teknisi atau admin NOC.
                </p>

                @if ($errors->any())
                <div class="mt-4 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] p-3.5 text-xs font-semibold text-[#991B1B]" role="alert">
                    @foreach ($errors->all() as $err)
                    <p>{{ $err }}</p>
                    @endforeach
                </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('login.submit') }}"
                    class="mt-5 space-y-4"
                    @submit="submitting = true">
                    @csrf

                    <div>
                        <label for="username" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                            Username
                        </label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            autocomplete="username"
                            autocapitalize="none"
                            placeholder="Contoh: andri atau admin"
                            class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70 focus:border-[#005F99]">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#06233B]">
                            Password
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password akun"
                            class="mt-1.5 block min-h-[46px] w-full rounded-lg border-[1.5px] border-[#5B82A6] bg-white px-3.5 py-2.5 text-sm font-medium text-[#06233B] placeholder-[#2C5270]/70 focus:border-[#005F99]">
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-[#005F99] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E] disabled:cursor-not-allowed disabled:opacity-60">
                            <svg
                                x-show="submitting"
                                x-cloak
                                class="h-4 w-4 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Memeriksa Akun...' : 'Masuk ke Formulir PSB'">Masuk ke Formulir PSB</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection