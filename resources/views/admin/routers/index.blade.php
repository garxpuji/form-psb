@extends('layouts.admin', ['title' => 'Master Data Router MikroTik'])

@section('content')
<div class="space-y-4 sm:space-y-5" x-data="{ editingRouter: null }">
    <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5">
        <h1 class="text-[clamp(1.125rem,2vw,1.35rem)] font-extrabold text-[#06233B]">Master Data Router MikroTik</h1>
        <p class="mt-0.5 text-xs font-medium text-[#2C5270]">
            Koneksi RouterOS API untuk PPPoE Server dan router distribusi Simple Queue. Port <code class="rounded bg-[#D1FAE5] px-1.5 py-0.5 font-mono font-bold text-[#065F46]">8729</code> otomatis menggunakan TLS.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12 xl:items-start">
        <div class="rounded-2xl border-[1.5px] border-[#4A7396] bg-white p-4 shadow-sm sm:p-5 xl:col-span-4" x-data="{ isPppoe: false, createPort: 8728 }">
            <h2 class="text-base font-extrabold text-[#06233B]">Tambah Router Baru</h2>
            <form method="POST" action="{{ route('admin.routers.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="nama_router" class="block text-xs font-bold uppercase text-[#06233B]">Nama Router</label>
                    <input
                        id="nama_router"
                        type="text"
                        name="nama_router"
                        required
                        placeholder="Contoh: RO_POS1, RO_FK, RO_GAYUH"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label for="ip_host" class="block text-xs font-bold uppercase text-[#06233B]">IP / Host</label>
                        <input
                            id="ip_host"
                            type="text"
                            name="ip_host"
                            required
                            placeholder="10.10.1.1"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div>
                        <label for="api_port" class="block text-xs font-bold uppercase text-[#06233B]">Port API</label>
                        <input
                            id="api_port"
                            type="number"
                            name="api_port"
                            x-model="createPort"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 rounded-lg border border-[#7FA6C9] bg-[#EDF5FC] px-3 py-1.5 text-xs">
                    <span class="font-semibold text-[#2C5270]" x-text="Number(createPort) === 8729 ? 'Transport: API-SSL (TLS Aktif)' : 'Transport: Standar (8728)'"></span>
                    <button
                        type="button"
                        @click="createPort = Number(createPort) === 8729 ? 8728 : 8729"
                        class="inline-flex min-h-[44px] shrink-0 items-center rounded-lg border border-[#4A7396] bg-white px-3 font-mono font-bold text-[#005F99] hover:bg-[#E0F2FE]"
                        x-text="Number(createPort) === 8729 ? 'Pakai 8728' : 'Pakai 8729 (TLS)'"></button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="router_username" class="block text-xs font-bold uppercase text-[#06233B]">Username API</label>
                        <input
                            id="router_username"
                            type="text"
                            name="username"
                            required
                            autocomplete="off"
                            placeholder="api-psb"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div>
                        <label for="router_password" class="block text-xs font-bold uppercase text-[#06233B]">Password API</label>
                        <input
                            id="router_password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Sandi API"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                </div>

                <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] px-3.5 py-1">
                    <label for="create_is_pppoe_server" class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm font-bold text-[#06233B]">
                        <input
                            id="create_is_pppoe_server"
                            type="checkbox"
                            name="is_pppoe_server"
                            value="1"
                            x-model="isPppoe"
                            class="h-5 w-5 shrink-0 rounded border-[#4A7396] text-[#005F99]">
                        <span>Jadikan Server PPPoE Utama</span>
                    </label>
                </div>

                <div x-show="!isPppoe">
                    <label for="lan_interface" class="block text-xs font-bold uppercase text-[#06233B]">LAN Interface (Static ARP)</label>
                    <input
                        id="lan_interface"
                        type="text"
                        name="lan_interface"
                        value="bridge-local"
                        placeholder="Contoh: bridge-local"
                        class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                </div>

                <button
                    type="submit"
                    class="flex min-h-[46px] w-full items-center justify-center rounded-xl bg-[#005F99] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#054A78] active:bg-[#043A5E]">
                    Tambah Router
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border-[1.5px] border-[#4A7396] bg-white shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#7FA6C9] text-left text-sm">
                    <thead class="bg-[#072A47] text-xs font-bold uppercase tracking-wider text-white">
                        <tr>
                            <th scope="col" class="px-4 py-3">Router</th>
                            <th scope="col" class="px-4 py-3">Kode Queue / Sales</th>
                            <th scope="col" class="px-4 py-3">Host &amp; Port</th>
                            <th scope="col" class="px-4 py-3">Slot IP</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#7FA6C9] bg-white">
                        @forelse ($routers as $router)
                            <tr class="hover:bg-[#EDF5FC]/70">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-extrabold text-[#06233B]">{{ $router->nama_router }}</div>
                                    <div class="mt-1">
                                        @if ($router->is_pppoe_server)
                                            <span class="inline-flex items-center rounded border border-[#0284C7] bg-[#E0F2FE] px-2 py-0.5 font-sans text-[11px] font-bold text-[#075985]">
                                                PPPoE Server
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded border border-[#D97706] bg-[#FEF3C7] px-2 py-0.5 font-sans text-[11px] font-bold text-[#78350F]">
                                                Distribusi IP
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if ($router->is_pppoe_server)
                                        <span class="inline-flex items-center rounded border border-[#0284C7] bg-[#E0F2FE] px-2.5 py-1 font-mono text-xs font-bold text-[#075985]">
                                            Semua Sales (PPPoE 01–17)
                                        </span>
                                    @elseif (str_contains(strtoupper($router->nama_router), 'POS1'))
                                        <div class="flex flex-wrap gap-1">
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">PS</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">SALES</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">BOY</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">ND</span>
                                            <span class="inline-flex items-center rounded bg-[#FEF3C7] px-1.5 py-0.5 font-mono text-xs font-bold text-[#92400E]">LITE</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">SANURI</span>
                                            <span class="inline-flex items-center rounded bg-[#D1FAE5] px-1.5 py-0.5 font-mono text-xs font-bold text-[#065F46]">SEWAN</span>
                                            <span class="inline-flex items-center rounded bg-[#D1FAE5] px-1.5 py-0.5 font-mono text-xs font-bold text-[#065F46]">SKM</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">RK</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">KN</span>
                                        </div>
                                    @elseif (str_contains(strtoupper($router->nama_router), 'FK'))
                                        <div class="flex flex-wrap gap-1">
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">FK</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">SN</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">JB</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">JN</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">KD</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">PN</span>
                                            <span class="inline-flex items-center rounded bg-[#E0F2FE] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">PGB</span>
                                        </div>
                                    @elseif (str_contains(strtoupper($router->nama_router), 'GAYUH'))
                                        <div class="flex flex-wrap gap-1">
                                            <span class="inline-flex items-center rounded bg-[#FEE2E2] px-2 py-0.5 font-mono text-xs font-bold text-[#991B1B]">GAMER (&gt;20M)</span>
                                        </div>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @forelse ($router->sales as $sale)
                                                <span class="inline-flex items-center rounded bg-[#EDF5FC] px-1.5 py-0.5 font-mono text-xs font-bold text-[#005F99]">{{ $sale->kode_sales_ip }}</span>
                                            @empty
                                                <span class="text-xs text-[#2C5270]">-</span>
                                            @endforelse
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    <div class="font-mono text-xs font-bold text-[#005F99]">
                                        {{ $router->ip_host }}:{{ $router->api_port }}
                                    </div>
                                    <div class="mt-0.5 text-[11px] text-[#2C5270]">
                                        {{ $router->username }}
                                        @if (! $router->is_pppoe_server && $router->lan_interface)
                                            · <span class="font-mono">{{ $router->lan_interface }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    @if ($router->is_pppoe_server)
                                        <span class="font-medium text-[#2C5270]">Khusus PPPoE</span>
                                    @else
                                        <div class="font-mono font-bold {{ (int) $router->available_slots_count === 0 ? 'text-[#991B1B]' : 'text-[#065F46]' }}">{{ $router->available_slots_count }} Kosong</div>
                                        <div class="font-mono text-[#2C5270]">{{ $router->used_slots_count }} Terpakai</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <x-noc.connection-test
                                            :action="route('admin.routers.test-connection', $router)"
                                            label="Tes Koneksi"
                                            :target="$router->nama_router" />
                                        <button
                                            type="button"
                                            @click="editingRouter = @js([
                                                'id' => $router->id,
                                                'nama_router' => $router->nama_router,
                                                'ip_host' => $router->ip_host,
                                                'api_port' => $router->api_port,
                                                'username' => $router->username,
                                                'lan_interface' => $router->lan_interface,
                                                'is_pppoe_server' => $router->is_pppoe_server,
                                            ])"
                                            class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-3 py-2 font-bold text-[#06233B] hover:bg-[#E0F2FE]">
                                            Edit
                                        </button>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.routers.destroy', $router) }}"
                                            class="inline-block"
                                            onsubmit="return confirm('Hapus router {{ $router->nama_router }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex min-h-[44px] items-center rounded-lg border border-[#DC2626] bg-[#FEE2E2] px-3 py-2 font-bold text-[#991B1B] hover:bg-[#FECACA]">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-[#2C5270]">
                                    Belum ada router terdaftar. Tambahkan router MikroTik melalui formulir di sebelah kiri.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        x-show="editingRouter !== null"
        x-cloak
        x-trap.inert.noscroll="editingRouter !== null"
        @keydown.escape.window="editingRouter = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#06233B]/80 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-router-modal-title">
        <template x-if="editingRouter">
            <div class="w-full max-w-md rounded-2xl border-2 border-[#005F99] bg-white p-5 shadow-2xl sm:p-6">
                <h3 id="edit-router-modal-title" class="text-base font-extrabold text-[#06233B]" x-text="'Edit Router: ' + editingRouter.nama_router"></h3>
                <form method="POST" :action="'/admin/routers/' + editingRouter.id" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit_nama_router" class="block text-xs font-bold uppercase text-[#06233B]">Nama Router</label>
                        <input
                            id="edit_nama_router"
                            type="text"
                            name="nama_router"
                            x-model="editingRouter.nama_router"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label for="edit_ip_host" class="block text-xs font-bold uppercase text-[#06233B]">IP / Host</label>
                            <input
                                id="edit_ip_host"
                                type="text"
                                name="ip_host"
                                x-model="editingRouter.ip_host"
                                required
                                class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                        </div>
                        <div>
                            <label for="edit_api_port" class="block text-xs font-bold uppercase text-[#06233B]">Port API</label>
                            <input
                                id="edit_api_port"
                                type="number"
                                name="api_port"
                                x-model="editingRouter.api_port"
                                required
                                class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                        </div>
                    </div>
                    <div>
                        <label for="edit_router_username" class="block text-xs font-bold uppercase text-[#06233B]">Username API</label>
                        <input
                            id="edit_router_username"
                            type="text"
                            name="username"
                            x-model="editingRouter.username"
                            required
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div>
                        <label for="edit_router_password" class="block text-xs font-bold uppercase text-[#06233B]">
                            Password API Baru (Kosongkan jika tidak diubah)
                        </label>
                        <input
                            id="edit_router_password"
                            type="password"
                            name="password"
                            placeholder="Biarkan kosong untuk mempertahankan password lama"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 text-sm font-semibold text-[#06233B]">
                    </div>
                    <div class="rounded-xl border border-[#4A7396] bg-[#EDF5FC] px-3.5 py-1">
                        <label for="edit_is_pppoe_server" class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm font-bold text-[#06233B]">
                            <input
                                id="edit_is_pppoe_server"
                                type="checkbox"
                                name="is_pppoe_server"
                                value="1"
                                x-model="editingRouter.is_pppoe_server"
                                class="h-5 w-5 shrink-0 rounded border-[#4A7396] text-[#005F99]">
                            <span>Server PPPoE Utama</span>
                        </label>
                    </div>
                    <div x-show="!editingRouter.is_pppoe_server">
                        <label for="edit_lan_interface" class="block text-xs font-bold uppercase text-[#06233B]">LAN Interface</label>
                        <input
                            id="edit_lan_interface"
                            type="text"
                            name="lan_interface"
                            x-model="editingRouter.lan_interface"
                            class="mt-1 block min-h-[44px] w-full rounded-lg border-[1.5px] border-[#4A7396] bg-white px-3 py-2 font-mono text-sm font-bold text-[#06233B]">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingRouter = null"
                            class="min-h-[44px] rounded-lg border-[1.5px] border-[#4A7396] bg-[#EDF5FC] px-4 py-2 text-xs font-bold text-[#06233B]">
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="min-h-[44px] rounded-lg bg-[#005F99] px-4 py-2 text-xs font-bold text-white hover:bg-[#054A78]">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</div>
@endsection
