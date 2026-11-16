<!DOCTYPE html>
<html lang="id" class="h-full bg-[#EDF5FC] text-[#06233B]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#072A47">
    <link rel="manifest" href="/manifest.json">

    <title>{{ $title ?? 'Panel NOC' }} - {{ config('app.name', 'ISP Auto-PSB') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #005F99 !important;
            outline-offset: 2px !important;
        }

        .ocean-sidebar a:focus-visible,
        .ocean-sidebar button:focus-visible {
            outline: 2px solid #BAE6FD !important;
            outline-offset: 2px !important;
        }
    </style>
</head>

<body class="min-h-full bg-[#EDF5FC] text-[#06233B] antialiased" x-data="{ mobileMenuOpen: false }" @keydown.escape.window="mobileMenuOpen = false">
    @php
    $navItems = [
    ['route' => 'admin.dashboard', 'label' => 'Log PSB & Retry', 'desc' => 'Antrean konfigurasi & kirim ulang', 'active' => request()->routeIs('admin.dashboard')],
    ['route' => 'admin.riwayat.index', 'label' => 'Riwayat & Rekap PSB', 'desc' => 'Filter periode & rekap sales', 'active' => request()->routeIs('admin.riwayat.*')],
    ['route' => 'admin.slots.index', 'label' => 'Slot Monitor IP', 'desc' => 'Ketersediaan antrean CLIENT-*', 'active' => request()->routeIs('admin.slots.*')],
    ['route' => 'admin.odps.index', 'label' => 'Master ODP', 'desc' => 'Ubah mode PPPoE atau IP Static', 'active' => request()->routeIs('admin.odps.*')],
    ['route' => 'admin.routers.index', 'label' => 'Master Router', 'desc' => 'Server PPPoE & 6 router distribusi', 'active' => request()->routeIs('admin.routers.*')],
    ['route' => 'admin.sales.index', 'label' => 'Master Sales', 'desc' => 'Kode PAY, KD, PN, JN, JB', 'active' => request()->routeIs('admin.sales.*')],
    ['route' => 'admin.pakets.index', 'label' => 'Master Paket', 'desc' => 'Profil PPPoE & batas kecepatan', 'active' => request()->routeIs('admin.pakets.*')],
    ['route' => 'admin.telegram-groups.index', 'label' => 'Grup Telegram', 'desc' => 'ID grup & topik laporan tim', 'active' => request()->routeIs('admin.telegram-groups.*')],
    ['route' => 'admin.users.index', 'label' => 'Manajemen User', 'desc' => 'Akun teknisi & penugasan tim', 'active' => request()->routeIs('admin.users.*')],
    ];
    @endphp

    <div class="flex min-h-screen flex-col lg:flex-row">
        <header class="ocean-sidebar sticky top-0 z-40 border-b border-[#124670] bg-[#072A47] text-white lg:hidden">
            <div class="flex items-center justify-between px-4 py-2.5 sm:px-6">
                <a href="{{ route('admin.dashboard') }}" class="flex min-h-[44px] items-center gap-2.5 font-bold tracking-tight text-white">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#7DD3FC] bg-[#005F99] font-mono text-xs font-extrabold text-white">
                        NOC
                    </span>
                    <div>
                        <div class="text-sm font-bold leading-tight text-white">Auto-PSB Control</div>
                        <div class="text-[11px] text-[#BAE6FD]">Panel Jaringan ISP</div>
                    </div>
                </a>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('psb.create') }}"
                        class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC] bg-[#0B3B63] px-3 py-2 text-xs font-bold text-white hover:bg-[#005F99] active:bg-[#043A5E]">
                        Form PSB
                    </a>

                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
                        class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-[#7DD3FC] bg-[#005F99] px-3 py-2 text-xs font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span>Menu NOC</span>
                    </button>
                </div>
            </div>

            <nav x-show="mobileMenuOpen" x-cloak class="border-t border-[#124670] bg-[#072A47] px-4 py-3 sm:px-6" aria-label="Navigasi Mobile NOC">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($navItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="flex min-h-[46px] flex-col justify-center rounded-lg border px-3.5 py-2 text-left transition {{ $item['active'] ? 'border-[#7DD3FC] bg-[#005F99] text-white font-bold' : 'border-[#124670] bg-[#0B3B63]/70 text-[#E0F2FE] hover:bg-[#0B3B63]' }}">
                        <span class="text-sm">{{ $item['label'] }}</span>
                        <span class="text-[11px] text-[#BAE6FD]">{{ $item['desc'] }}</span>
                    </a>
                    @endforeach
                </div>

                <div class="mt-3 flex items-center justify-between border-t border-[#124670] pt-3">
                    <div class="text-xs text-[#BAE6FD]">
                        Akun aktif: <span class="font-bold text-white">{{ auth()->user()->name }}</span> ({{ strtoupper(auth()->user()->role) }})
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC] bg-[#0B3B63] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#991B1B] active:bg-[#7F1D1D]">
                            Keluar Akun
                        </button>
                    </form>
                </div>
            </nav>
        </header>

        <aside class="ocean-sidebar hidden w-64 shrink-0 flex-col justify-between border-r border-[#124670] bg-[#072A47] text-white lg:flex">
            <div class="p-4">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl border border-[#174E7C] bg-[#0B3B63] p-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#7DD3FC] bg-[#005F99] font-mono text-xs font-extrabold text-white">
                        NOC
                    </span>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-extrabold tracking-tight text-white">Auto-PSB Control</div>
                        <div class="truncate text-xs text-[#BAE6FD]">MikroTik & Telegram</div>
                    </div>
                </a>

                <div class="mt-3">
                    <a
                        href="{{ route('psb.create') }}"
                        class="flex min-h-[44px] w-full items-center justify-between rounded-lg border border-[#7DD3FC] bg-[#005F99] px-3.5 py-2.5 text-xs font-bold text-white transition hover:bg-[#054A78] active:bg-[#043A5E]">
                        <span>Buka Form PSB Teknisi</span>
                        <span class="font-mono text-[11px] text-[#E0F2FE]">/psb/create</span>
                    </a>
                </div>

                <nav class="mt-4 space-y-1.5" aria-label="Navigasi Utama NOC">
                    @foreach ($navItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="block min-h-[46px] rounded-lg border px-3 py-2.5 transition {{ $item['active'] ? 'border-[#7DD3FC] bg-[#005F99] text-white shadow-sm' : 'border-transparent text-[#E0F2FE] hover:border-[#174E7C] hover:bg-[#0B3B63] hover:text-white' }}">
                        <div class="text-sm font-bold leading-snug">{{ $item['label'] }}</div>
                        <div class="mt-0.5 text-[11px] text-[#BAE6FD]">{{ $item['desc'] }}</div>
                    </a>
                    @endforeach
                </nav>
            </div>

            <div class="border-t border-[#124670] bg-[#051F36] p-4">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-bold text-white">{{ auth()->user()->name }}</div>
                        <div class="font-mono text-[11px] text-[#BAE6FD]">ROLE: {{ strtoupper(auth()->user()->role) }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex min-h-[44px] items-center rounded-lg border border-[#7DD3FC]/60 bg-[#0B3B63] px-3 py-2 text-xs font-bold text-white transition hover:border-white hover:bg-[#991B1B]">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <div class="hidden items-center justify-between border-b border-[#B8D4EC] bg-white px-6 py-3 lg:flex">
                <span class="text-sm font-bold text-[#06233B]">
                    {{ $title ?? 'Panel Kendali Auto-Provisioning PSB' }}
                </span>
                <div class="flex items-center gap-4 text-xs text-[#2C5270]">
                    <span>Database: <span class="font-mono font-bold text-[#06233B]">MariaDB InnoDB</span></span>
                    <span>Jadwal Sync Slot: <span class="font-mono font-bold text-[#06233B]">Tiap 15 Menit</span></span>
                </div>
            </div>

            <main class="w-full flex-1 px-3.5 py-4 sm:px-6 sm:py-5 lg:px-8">
                @if (session('success'))
                <div class="mb-4 rounded-xl border-2 border-[#059669] bg-[#D1FAE5] p-3.5 text-sm text-[#065F46]" role="status">
                    <div class="font-bold">Perubahan Tersimpan</div>
                    <div class="mt-0.5 whitespace-pre-line">{{ session('success') }}</div>
                </div>
                @endif

                @if (session('error'))
                <div class="mb-4 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] p-3.5 text-sm text-[#991B1B]" role="alert">
                    <div class="font-bold">Gagal Memproses</div>
                    <div class="mt-0.5 whitespace-pre-line">{{ session('error') }}</div>
                </div>
                @endif

                @if ($errors->any())
                <div class="mb-4 rounded-xl border-2 border-[#DC2626] bg-[#FEE2E2] p-3.5 text-sm text-[#991B1B]" role="alert">
                    <div class="font-bold">Periksa kembali isian berikut:</div>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>